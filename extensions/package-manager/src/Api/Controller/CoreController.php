<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\ExtensionManager\Api\Controller;

use Flarum\ExtensionManager\Host\StorageDir;
use Flarum\ExtensionManager\Lang;
use Flarum\ExtensionManager\Plan\CoreUpgrade;
use Flarum\Foundation\Application;
use Flarum\Foundation\Paths;
use Flarum\Http\RequestUtil;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Flarum itself: what version is here, what version exists, and what upgrading
 * would mean for the extensions installed.
 *
 * 🚨 A read that changes nothing, asked before anything is pressed. Updating
 * core is the one operation where the blast radius is the whole forum, and the
 * current tooling's answer to "will this work" is to try it and read the
 * constraint error — which names packages, not extensions, at the end of a long
 * wait.
 */
class CoreController implements RequestHandlerInterface
{
    public function __construct(
        private Paths $paths,
        private Application $app,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        RequestUtil::getActor($request)->assertAdmin();

        $current = ltrim($this->app->version(), 'v');
        $target = trim((string) ($request->getQueryParams()['target'] ?? ''));

        $lock = (array) json_decode((string) @file_get_contents($this->paths->base.'/composer.lock'), true);
        $packages = array_values((array) ($lock['packages'] ?? []));

        if ($packages === []) {
            return new JsonResponse([
                'error' => Lang::trans('core.no_lock'),
            ], 500);
        }

        if ($target === '') {
            /*
             * 🚨 No target means no pre-flight. Answering "would my extensions
             * survive?" against a version nobody has named would be answering a
             * question nobody asked, at the cost of a Packagist call per
             * extension that fails the cheap test.
             */
            return new JsonResponse([
                'current' => $current,
                'newest' => $this->newestCore(),
                'preflight' => null,
            ]);
        }

        /*
         * 🚨 The cheap pass only. Rows the lock cannot clear come back in
         * `pending`, and the screen asks about those through /discover/compat in
         * capped batches — the endpoint that already caches, already validates
         * package names, and already limits how many outbound calls one request
         * can cause.
         *
         * Doing it here took 28 seconds against this dev forum's 58 extensions.
         */
        $preflight = (new CoreUpgrade())->preflight($packages, $target);

        return new JsonResponse([
            'current' => $current,
            'newest' => $this->newestCore(),
            'preflight' => $preflight,
        ]);
    }

    /**
     * The newest core the cheap nightly check knows about.
     *
     * 🚨 Read from that check's cache rather than asked fresh. This endpoint is
     * opened by somebody browsing a settings page, and making it reach Packagist
     * on every page view would put an outbound call on a page load — the kind of
     * thing that is invisible until the day Packagist is slow and every admin
     * page hangs with it.
     */
    private function newestCore(): ?array
    {
        /*
         * 🚨 Measured against what is installed, as the extension cards are.
         * The raw saved check still listed rc.8 → 2.0.0 after core had moved,
         * so the panel read "This forum runs Flarum 2.0.0" above "Update
         * Flarum to v2.0.0" until the next check (ClaudiusH, 2026-10-09).
         */
        $updates = (new \Flarum\ExtensionManager\Work\UpdateCheck(StorageDir::of($this->paths->storage).'/updates.json'))
            ->current($this->paths->base.'/composer.lock')['updates'] ?? [];

        return $updates['flarum/core'] ?? null;
    }
}
