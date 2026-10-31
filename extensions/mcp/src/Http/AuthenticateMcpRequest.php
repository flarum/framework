<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Mcp\Http;

use Flarum\Http\AccessToken;
use Flarum\Http\DeveloperAccessToken;
use Flarum\Http\RequestUtil;
use Flarum\Http\UrlGenerator;
use Flarum\Mcp\Capability\CapabilityResolver;
use Flarum\Mcp\Capability\McpContext;
use Flarum\Mcp\Capability\ResolvedCapabilities;
use Flarum\User\Guest;
use Flarum\User\User;
use Laminas\Diactoros\Response\JsonResponse;
use Mcp\Server\Transport\StreamableHttpTransport;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Derives the MCP actor and scopes from the `Authorization` header alone, then
 * applies the `mcp.use` gate and the write-scope check.
 *
 * The session cookie is never consulted (the caller has already discarded it),
 * which is what makes the route's CSRF exemption safe.
 */
class AuthenticateMcpRequest implements MiddlewareInterface
{
    public const string AUTHENTICATION = 'mcp.authentication';
    public const string CAPABILITIES = 'mcp.capabilities';

    public const string PERMISSION = 'mcp.use';

    public function __construct(
        protected OAuthTokenVerifier $oauth,
        protected CapabilityResolver $resolver,
        protected UrlGenerator $url,
        protected TranslatorInterface $translator,
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // A CORS preflight carries no credentials and runs nothing.
        if ($request->getMethod() === 'OPTIONS') {
            return $handler->handle($this->withAuthentication($request, new McpAuthentication(new Guest(), [], McpAuthentication::GUEST)));
        }

        $authentication = $this->authenticate($request);

        if (! $authentication) {
            return $this->unauthorized('invalid_token');
        }

        $actor = $authentication->actor;

        if (! $actor->can(static::PERMISSION)) {
            return $actor->isGuest()
                ? $this->unauthorized()
                : new JsonResponse([
                    'error' => 'forbidden',
                    'error_description' => $this->translator->trans('flarum-mcp.api.forbidden_description'),
                ], 403);
        }

        if (! $actor->isGuest()) {
            // Throttled inside the model, as in AuthenticateWithHeader.
            $actor->updateLastSeen();

            if ($actor->isDirty()) {
                $actor->save();
            }
        }

        $capabilities = $this->resolver->resolve($actor);

        if ($this->callsWriteTool($request, $capabilities) && ! in_array(McpContext::SCOPE_WRITE, $authentication->scopes, true)) {
            return $actor->isGuest()
                ? $this->unauthorized()
                : $this->insufficientScope();
        }

        return $handler->handle(
            $this->withAuthentication($request, $authentication)->withAttribute(static::CAPABILITIES, $capabilities)
        );
    }

    /**
     * Null when the request carries credentials that are not usable here.
     */
    protected function authenticate(ServerRequestInterface $request): ?McpAuthentication
    {
        $header = trim($request->getHeaderLine('Authorization'));

        if ($header === '') {
            return new McpAuthentication(new Guest(), [McpContext::SCOPE_READ], McpAuthentication::GUEST);
        }

        // Core's `Token` scheme (API keys and access tokens) is refused: a
        // master API key must never become an MCP identity.
        if (! preg_match('/^Bearer\s+(\S+)$/i', $header, $matches)) {
            return null;
        }

        $credential = $matches[1];

        if (substr_count($credential, '.') === 2) {
            $token = $this->oauth->verify($credential, $this->resourceUri());

            return $token
                ? new McpAuthentication($token['user'], $token['scopes'], McpAuthentication::OAUTH)
                : null;
        }

        $token = AccessToken::findValid($credential);

        // Session and remember-me tokens belong to a browser, not to a client.
        if (! $token instanceof DeveloperAccessToken || ! $token->user instanceof User) {
            return null;
        }

        $token->touch(request: $request);

        return new McpAuthentication(
            $token->user,
            [McpContext::SCOPE_READ, McpContext::SCOPE_WRITE],
            McpAuthentication::DEVELOPER_TOKEN,
        );
    }

    /**
     * Peeks at the JSON-RPC body (each message of a batch) for a `tools/call`
     * of a tool that is not read-only. The body itself is left untouched.
     */
    protected function callsWriteTool(ServerRequestInterface $request, ResolvedCapabilities $capabilities): bool
    {
        if ($request->getMethod() !== 'POST') {
            return false;
        }

        $body = $request->getBody();
        $size = $body->getSize();

        // An oversized body is refused by the transport before anything runs.
        if ($size !== null && $size > StreamableHttpTransport::DEFAULT_MAX_BODY_BYTES) {
            return false;
        }

        $decoded = json_decode((string) $body, true);

        if (! is_array($decoded)) {
            return false;
        }

        $messages = array_is_list($decoded) ? $decoded : [$decoded];

        foreach ($messages as $message) {
            if (! is_array($message) || ($message['method'] ?? null) !== 'tools/call') {
                continue;
            }

            $name = $message['params']['name'] ?? null;
            $tool = is_string($name) ? $capabilities->tool($name) : null;

            if ($tool && ! $tool->readOnly()) {
                return true;
            }
        }

        return false;
    }

    protected function withAuthentication(ServerRequestInterface $request, McpAuthentication $authentication): ServerRequestInterface
    {
        return RequestUtil::withActor($request, $authentication->actor)
            ->withAttribute(static::AUTHENTICATION, $authentication);
    }

    protected function unauthorized(?string $error = null): ResponseInterface
    {
        // RFC 6750 §3.1: a request without credentials gets the challenge but no error code.
        $challenge = $this->challenge(array_filter([
            'error' => $error,
            'resource_metadata' => $this->resourceMetadataUri(),
            'scope' => McpContext::SCOPE_READ.' '.McpContext::SCOPE_WRITE,
        ]));

        return new JsonResponse(array_filter([
            'error' => $error ?? 'unauthorized',
            'error_description' => $this->translator->trans($error ? 'flarum-mcp.api.invalid_token' : 'flarum-mcp.api.requires_sign_in'),
        ]), 401, ['WWW-Authenticate' => $challenge]);
    }

    protected function insufficientScope(): ResponseInterface
    {
        $challenge = $this->challenge([
            'error' => 'insufficient_scope',
            'scope' => McpContext::SCOPE_READ.' '.McpContext::SCOPE_WRITE,
            'resource_metadata' => $this->resourceMetadataUri(),
        ]);

        return new JsonResponse([
            'error' => 'insufficient_scope',
            'error_description' => $this->translator->trans('flarum-mcp.api.requires_write_scope'),
        ], 403, ['WWW-Authenticate' => $challenge]);
    }

    /**
     * @param array<string, string> $parameters
     */
    protected function challenge(array $parameters): string
    {
        $parts = [];

        foreach ($parameters as $key => $value) {
            $parts[] = $key.'="'.addcslashes($value, '"\\').'"';
        }

        return 'Bearer '.implode(', ', $parts);
    }

    protected function resourceUri(): string
    {
        return $this->url->to('forum')->route('mcp');
    }

    protected function resourceMetadataUri(): string
    {
        return $this->url->to('forum')->route('mcp.resourceMetadata');
    }
}
