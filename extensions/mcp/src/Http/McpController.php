<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Mcp\Http;

use Flarum\Api\JsonApi;
use Flarum\Http\RequestUtil;
use Flarum\Http\UrlGenerator;
use Flarum\Mcp\Api\ApiClient;
use Flarum\Mcp\Capability\CapabilityResolver;
use Flarum\Mcp\Capability\McpContext;
use Flarum\Mcp\Capability\ResolvedCapabilities;
use Flarum\Mcp\Server\ServerFactory;
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\User\Guest;
use Illuminate\Contracts\Container\Container;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * The `/mcp` endpoint: authenticate, then hand the request to a server built
 * for the actor.
 */
class McpController implements RequestHandlerInterface
{
    public function __construct(
        protected AuthenticateMcpRequest $authenticate,
        protected CapabilityResolver $resolver,
        protected ServerFactory $servers,
        protected JsonApi $api,
        protected Container $container,
        protected UrlGenerator $url,
        protected TranslatorInterface $translator,
        protected SettingsRepositoryInterface $settings,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        // Whatever the forum's session middleware found is discarded: only the
        // Authorization header identifies an MCP client.
        $request = RequestUtil::withActor($request->withoutAttribute('session'), new Guest());

        return $this->authenticate->process($request, new class(fn (ServerRequestInterface $request) => $this->serve($request)) implements RequestHandlerInterface {
            /**
             * @param \Closure(ServerRequestInterface): ResponseInterface $serve
             */
            public function __construct(protected \Closure $serve)
            {
            }

            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return ($this->serve)($request);
            }
        });
    }

    protected function serve(ServerRequestInterface $request): ResponseInterface
    {
        /** @var McpAuthentication $authentication */
        $authentication = $request->getAttribute(AuthenticateMcpRequest::AUTHENTICATION);
        $actor = $authentication->actor;

        $capabilities = $request->getAttribute(AuthenticateMcpRequest::CAPABILITIES);

        if (! $capabilities instanceof ResolvedCapabilities) {
            $capabilities = $this->resolver->resolve($actor);
        }

        $context = new McpContext(
            actor: $actor,
            scopes: $authentication->scopes,
            api: new ApiClient($this->api, $this->container, $actor, $request),
            url: $this->url,
            translator: $this->translator,
            settings: $this->settings,
            request: $request,
        );

        return $this->servers
            ->make($context, $capabilities)
            ->run($this->servers->transport($request));
    }
}
