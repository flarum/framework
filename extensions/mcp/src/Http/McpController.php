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
use Flarum\Locale\LocaleManager;
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\User\Guest;
use Flarum\User\User;
use Illuminate\Session\NullSessionHandler;
use Illuminate\Session\Store;
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
        protected LocaleManager $locales,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        // StartSession saves the session it started after this returns: with a
        // null handler there is nothing to save. See WithoutSession.
        $session = $request->getAttribute('session');

        if ($session instanceof Store) {
            $session->setHandler(new NullSessionHandler());
        }

        // A CORS preflight is answered by the transport's CORS and host checks
        // alone: no actor, no capabilities, no server.
        if ($request->getMethod() === 'OPTIONS') {
            return $this->servers->transport($request)->listen();
        }

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

    /**
     * The forum's SetLocale ran for the discarded session's actor: the bearer's
     * own locale preference applies instead, or else the forum default.
     */
    protected function applyLocale(User $actor): void
    {
        foreach ([$actor->isGuest() ? null : $actor->getPreference('locale'), $this->settings->get('default_locale')] as $locale) {
            if (is_string($locale) && $this->locales->hasLocale($locale)) {
                $this->locales->setLocale($locale);

                return;
            }
        }
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

        $this->applyLocale($actor);

        $context = new McpContext(
            actor: $actor,
            scopes: $authentication->scopes,
            api: new ApiClient($this->api, $this->container, $actor, $authentication->scopes, $request),
            url: $this->url,
            translator: $this->translator,
            settings: $this->settings,
            request: $request,
        );

        // Only the handshake and discovery answers carry the instructions, which
        // build the whole forum overview.
        $messages = $request->getAttribute(AuthenticateMcpRequest::MESSAGES);
        $withInstructions = $messages instanceof JsonRpcMessages
            && ($messages->has('initialize') || $messages->has('server/discover'));

        return $this->servers
            ->make($context, $capabilities, $withInstructions)
            ->run($this->servers->transport($request));
    }
}
