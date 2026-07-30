<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Api\Middleware;

use Flarum\Frontend\Compiler\AssetsRevision;
use Flarum\Frontend\RecompileFrontendAssets;
use Flarum\Locale\LocaleManager;
use Flarum\Settings\SettingsRepositoryInterface;
use Illuminate\Contracts\Container\Container;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface as Middleware;
use Psr\Http\Server\RequestHandlerInterface as Handler;

/**
 * Stamps every API response with the current asset revision token, so a browsing
 * client can notice when the JS/CSS it booted with has been superseded and offer
 * the user a reload — without polling or a forced refresh.
 */
class AddAssetsRevisionHeader implements Middleware
{
    public const HEADER_NAME = 'X-Flarum-Assets-Revision';

    public function __construct(
        protected Container $container,
        protected AssetsRevision $revision
    ) {
    }

    public function process(Request $request, Handler $handler): Response
    {
        // A long-lived tab only ever makes API requests, so this middleware is
        // its sole line to the asset revision. If the forum assets were flagged
        // dirty (e.g. by an extension toggle) they must be rebuilt before the
        // token is read — otherwise the token would be computed from the
        // not-yet-rebuilt manifest and never move for API-only clients, and no
        // reload prompt would appear until some unrelated visitor happened to
        // load a full page. The rebuild is in place and a no-op when the output
        // is unchanged, so only the first request after a toggle pays for it.
        $this->recompileDirtyAssets();

        return $handler->handle($request)->withHeader(self::HEADER_NAME, $this->revision->token());
    }

    protected function recompileDirtyAssets(): void
    {
        foreach (['flarum.assets.forum', 'flarum.assets.common'] as $assets) {
            (new RecompileFrontendAssets(
                $this->container->make($assets),
                $this->container->make(LocaleManager::class),
                $this->container->make('events'),
                $this->container->make(SettingsRepositoryInterface::class)
            ))->recompileIfDirty();
        }
    }
}
