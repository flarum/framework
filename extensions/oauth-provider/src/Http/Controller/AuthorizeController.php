<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\OAuthProvider\Http\Controller;

use Carbon\Carbon;
use Flarum\Http\AccessToken;
use Flarum\Http\RequestUtil;
use Flarum\Http\UrlGenerator;
use Flarum\OAuthProvider\ClientRegistration\ClientMetadataException;
use Flarum\OAuthProvider\ClientRegistration\MetadataDocumentClients;
use Flarum\OAuthProvider\Models\Client;
use Flarum\OAuthProvider\Models\Consent;
use Flarum\OAuthProvider\Scope\ScopeRegistry;
use Flarum\OAuthProvider\Server\AuthorizationServerFactory;
use Flarum\OAuthProvider\Server\AuthorizationServerMetadata;
use Flarum\OAuthProvider\Server\Entity\UserEntity;
use Flarum\OAuthProvider\Server\RequestType\AuthorizationRequest as ResourceAuthorizationRequest;
use Illuminate\Contracts\View\Factory;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\JsonResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use League\OAuth2\Server\AuthorizationServer;
use League\OAuth2\Server\Exception\OAuthServerException;
use League\OAuth2\Server\RequestTypes\AuthorizationRequest;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface;

class AuthorizeController implements RequestHandlerInterface
{
    public function __construct(
        protected AuthorizationServerFactory $factory,
        protected Factory $view,
        protected UrlGenerator $url,
        protected ScopeRegistry $scopes,
        protected MetadataDocumentClients $metadataDocuments,
        protected AuthorizationServerMetadata $metadata,
    ) {
    }

    public function handle(Request $request): ResponseInterface
    {
        // The consent screen must never be framed (clickjacking); the headers
        // go on every response so no branch can forget them.
        return $this->authorize($request)
            ->withHeader('X-Frame-Options', 'DENY')
            ->withHeader('Content-Security-Policy', "frame-ancestors 'none'");
    }

    protected function authorize(Request $request): ResponseInterface
    {
        $clientId = $request->getQueryParams()['client_id'] ?? null;

        if (is_string($clientId)) {
            try {
                $this->metadataDocuments->resolve($clientId, (string) $request->getAttribute('ipAddress'));
            } catch (ClientMetadataException $e) {
                return new JsonResponse(['error' => $e->error, 'error_description' => $e->getMessage()], 400);
            }
        }

        $server = $this->factory->authorizationServer();

        try {
            $authRequest = $server->validateAuthorizationRequest($request);
        } catch (OAuthServerException $exception) {
            return $this->withIssuer($exception->generateHttpResponse(new \Laminas\Diactoros\Response()));
        }

        $actor = RequestUtil::getActor($request);
        $queryParams = $request->getQueryParams();
        $prompt = $queryParams['prompt'] ?? null;
        $nonce = $queryParams['nonce'] ?? null;
        $maxAge = isset($queryParams['max_age']) ? (int) $queryParams['max_age'] : null;
        $prompts = is_string($prompt) ? preg_split('/\s+/', trim($prompt), -1, PREG_SPLIT_NO_EMPTY) : [];
        $promptNone = in_array('none', $prompts, true);

        $authTime = $this->sessionAuthTime($request);

        // OIDC max_age: if the user's session is older than max_age seconds (or
        // we can't determine when they last authenticated), force re-auth even
        // if they're currently logged in. prompt=login has the same effect.
        $needsReAuth = $actor->isGuest()
            || in_array('login', $prompts, true)
            || ($maxAge !== null && ($authTime === null || (Carbon::now()->getTimestamp() - $authTime->getTimestamp()) >= $maxAge));

        if ($needsReAuth) {
            if ($promptNone) {
                return $this->authorizationError($authRequest, 'login_required', 'The user needs to authenticate.');
            }

            $base = $this->url->to('forum')->base();

            // Build the return-to as a path+query on the configured forum URL so
            // the scheme matches (avoids http/https mismatches behind proxies).
            $uri = $request->getUri();
            $returnPath = $uri->getPath();
            if ($uri->getQuery() !== '') {
                $returnPath .= '?'.$uri->getQuery();
            }
            $returnTo = urlencode($base.$returnPath);

            return new RedirectResponse($base.'/?oauth_login=1&return_to='.$returnTo);
        }

        $clientId = $authRequest->getClient()->getIdentifier();
        $requestedScopes = array_map(fn ($scope) => $scope->getIdentifier(), $authRequest->getScopes());

        $params = $request->getParsedBody() ?? [];
        $method = strtoupper($request->getMethod());

        // The consent form was submitted — record the decision.
        if ($method === 'POST' && isset($params['oauth_consent_approved'])) {
            $approved = $params['oauth_consent_approved'] === '1';

            if ($approved) {
                $this->rememberConsent((int) $actor->id, $clientId, $requestedScopes);
            }

            return $this->completeAuthorization($authRequest, $server, $actor->id, $approved, $nonce, $authTime);
        }

        // Previously-granted consent still covers this request — skip the prompt
        // unless the client explicitly asked for re-consent via prompt=consent.
        if (! in_array('consent', $prompts, true) && $this->hasExistingConsent((int) $actor->id, $clientId, $requestedScopes)) {
            return $this->completeAuthorization($authRequest, $server, $actor->id, true, $nonce, $authTime);
        }

        if ($promptNone) {
            return $this->authorizationError($authRequest, 'interaction_required', 'The user needs to grant consent.');
        }

        $scopeDescriptions = [];

        foreach ($authRequest->getScopes() as $scope) {
            $scopeDescriptions[$scope->getIdentifier()] = $this->scopes->description($scope->getIdentifier()) ?? $scope->getIdentifier();
        }

        $csrfToken = $request->getAttribute('session')?->token();

        /** @var Client|null $clientModel */
        $clientModel = Client::query()->find($clientId);

        // Self-described clients (metadata document, dynamic registration)
        // choose their own name, so they are marked unverified and the user
        // is shown where they would be sent back to.
        $selfDescribed = $clientModel !== null && $clientModel->source !== Client::SOURCE_ADMIN;

        $html = $this->view->make('flarum-oauth-provider::consent', [
            'client' => $authRequest->getClient(),
            'unverified' => $selfDescribed,
            'sourceHost' => $selfDescribed ? $clientModel->sourceHost() : null,
            'redirectHost' => $selfDescribed ? $this->redirectHost($authRequest) : null,
            'scopes' => $scopeDescriptions,
            'actor' => $actor,
            'formAction' => (string) $request->getUri(),
            'csrfToken' => $csrfToken,
            'queryParams' => $queryParams,
        ])->render();

        return new HtmlResponse($html);
    }

    protected function completeAuthorization(
        AuthorizationRequest $authRequest,
        AuthorizationServer $server,
        int $userId,
        bool $approved,
        ?string $nonce = null,
        ?Carbon $authTime = null,
    ): ResponseInterface {
        $userEntity = new UserEntity();
        $userEntity->setIdentifier((string) $userId);

        $authRequest->setUser($userEntity);
        $authRequest->setAuthorizationApproved($approved);

        if ($authRequest instanceof ResourceAuthorizationRequest) {
            $authRequest->setNonce($nonce);
            $authRequest->setAuthTime(($authTime ?? Carbon::now())->toDateTimeImmutable());
        }

        try {
            $response = $this->withIssuer($server->completeAuthorizationRequest($authRequest, new \Laminas\Diactoros\Response()));
        } catch (OAuthServerException $exception) {
            return $this->withIssuer($exception->generateHttpResponse(new \Laminas\Diactoros\Response()));
        }

        return $response;
    }

    protected function redirectHost(AuthorizationRequest $authRequest): ?string
    {
        $redirectUri = $authRequest->getRedirectUri() ?? (array) $authRequest->getClient()->getRedirectUri();
        $redirectUri = is_array($redirectUri) ? (string) reset($redirectUri) : $redirectUri;
        $host = parse_url($redirectUri, PHP_URL_HOST);

        return is_string($host) && $host !== '' ? $host : null;
    }

    /**
     * Returns an OIDC interaction error to a previously validated redirect URI.
     */
    protected function authorizationError(AuthorizationRequest $authRequest, string $error, string $description): ResponseInterface
    {
        $redirectUri = $authRequest->getRedirectUri() ?? (array) $authRequest->getClient()->getRedirectUri();
        $redirectUri = is_array($redirectUri) ? (string) reset($redirectUri) : $redirectUri;
        $params = ['error' => $error, 'error_description' => $description];

        if ($authRequest->getState() !== null) {
            $params['state'] = $authRequest->getState();
        }

        return $this->withIssuer(new RedirectResponse($redirectUri.(str_contains($redirectUri, '?') ? '&' : '?').http_build_query($params)));
    }

    /**
     * Adds the RFC 9207 `iss` parameter to a redirect back to the client.
     */
    protected function withIssuer(ResponseInterface $response): ResponseInterface
    {
        $location = $response->getHeaderLine('Location');

        if ($location === '') {
            return $response;
        }

        $separator = str_contains($location, '?') ? '&' : '?';

        return $response->withHeader('Location', $location.$separator.http_build_query(['iss' => $this->metadata->issuer()]));
    }

    /**
     * Best-effort "when did the current session authenticate" timestamp. Uses
     * the session's access token creation time — Flarum reissues a fresh access
     * token each time a remember-me cookie restores a session, so this is the
     * most accurate "this session started" signal we have.
     *
     * Returns null for guests or if the session is orphaned.
     */
    protected function sessionAuthTime(Request $request): ?Carbon
    {
        $session = $request->getAttribute('session');
        $tokenValue = $session?->get('access_token');

        if (! is_string($tokenValue) || $tokenValue === '') {
            return null;
        }

        $token = AccessToken::findValid($tokenValue);

        return $token?->created_at;
    }

    protected function hasExistingConsent(int $userId, string $clientId, array $requestedScopes): bool
    {
        /** @var Consent|null $consent */
        $consent = Consent::query()
            ->where('user_id', $userId)
            ->where('client_id', $clientId)
            ->where('revoked', false)
            ->first();

        return $consent !== null && $consent->covers($requestedScopes);
    }

    protected function rememberConsent(int $userId, string $clientId, array $requestedScopes): void
    {
        /** @var Consent|null $existing */
        $existing = Consent::query()
            ->where('user_id', $userId)
            ->where('client_id', $clientId)
            ->first();

        if ($existing === null) {
            Consent::query()->create([
                'user_id' => $userId,
                'client_id' => $clientId,
                'scopes' => $requestedScopes,
                'revoked' => false,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);

            return;
        }

        // Merge newly-approved scopes into the existing grant and re-activate it
        // if it was previously revoked.
        $merged = array_values(array_unique(array_merge($existing->scopes ?? [], $requestedScopes)));

        $existing->scopes = $merged;
        $existing->revoked = false;
        $existing->updated_at = Carbon::now();
        $existing->save();
    }
}
