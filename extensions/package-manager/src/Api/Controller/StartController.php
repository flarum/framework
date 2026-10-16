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
use Flarum\ExtensionManager\Discover\Cache;
use Flarum\ExtensionManager\Discover\Compatibility;
use Flarum\ExtensionManager\Discover\Packagist;
use Flarum\ExtensionManager\Host\Capability;
use Flarum\ExtensionManager\Plan\Nightly;
use Flarum\ExtensionManager\Plan\Repin;
use Flarum\ExtensionManager\Run\Drivers;
use Flarum\ExtensionManager\Run\RunStore;
use Flarum\ExtensionManager\Run\StepRunner;
use Flarum\ExtensionManager\Work\WorkDir;
use Flarum\Extension\ExtensionManager;
use Flarum\Foundation\Application;
use Flarum\Foundation\Paths;
use Flarum\Http\RequestUtil;
use Illuminate\Support\Arr;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Start an update.
 *
 * 🚨 Every refusal here says why, in words aimed at the person reading them.
 * The behaviour being replaced refuses silently when anything looks busy, which
 * is how one dead task blocks every later update forever and the admin is left
 * looking at a spinner with no explanation anywhere.
 */
class StartController implements RequestHandlerInterface
{
    public function __construct(
        private RunStore $runs,
        private StepRunner $runner,
        private Drivers $drivers,
        private Paths $paths,
        private ExtensionManager $extensions,
        private Application $app,
    ) {
    }

    /**
     * 🚨 A replaced package is never installed under its old name, whatever
     * the screen showed. Installing v17development/flarum-seo gave Extension
     * Manager users FoF SEO under the wrong id, which then refused to enable
     * (discuss.flarum.org d/40012). Asked here, on the server, so a stale tab
     * or a hand-made request cannot get round it; the verdict is the cached one
     * the Find tab already fetched, so this costs nothing in the usual case.
     *
     * @param list<string> $packages
     */
    private function replaced(array $packages): ?string
    {
        $packagist = new Packagist(new Cache(StorageDir::of($this->paths->storage).'/packagist'));
        $verdicts = $packagist->verdicts(array_slice($packages, 0, 5), new Compatibility(ltrim($this->app->version(), 'v')));

        foreach ($verdicts as $name => $verdict) {
            if (! empty($verdict['replaced'])) {
                return $verdict['replacedBy']
                    ? Lang::trans('start.replaced_by', ['name' => $name, 'replacement' => $verdict['replacedBy']])
                    : Lang::trans('start.replaced', ['name' => $name]);
            }
        }

        return null;
    }

    /**
     * Why these packages cannot be removed, if they cannot.
     *
     * @param list<string> $packages
     */
    private function cannotRemove(array $packages): ?string
    {
        $json = (array) json_decode((string) @file_get_contents($this->paths->base.'/composer.json'), true);
        $direct = array_merge((array) ($json['require'] ?? []), (array) ($json['require-dev'] ?? []));

        foreach ($packages as $package) {
            /*
             * 🚨 The same guard the previous Extension Manager has, because it is the right
             * one: `composer remove` on something this site never asked for
             * fails with a message about a package not being required, which
             * reads like a bug. An extension that arrived as somebody else's
             * dependency goes when that dependency goes, and not before.
             */
            if (! isset($direct[$package])) {
                return Lang::trans('start.not_direct', ['package' => $package]);
            }

            /*
             * 🚨 A guard the previous Extension Manager does NOT have, and the reason is
             * ordering: taking the files away from an extension that is still
             * switched on leaves Flarum with an enabled extension it cannot
             * load. Disabling first is one click, and it is the click that makes
             * the removal safe.
             */
            if ($this->isEnabled($package)) {
                return Lang::trans('start.still_enabled', ['package' => $package]);
            }
        }

        return null;
    }

    private function isEnabled(string $package): bool
    {
        foreach ($this->extensions->getExtensions() as $extension) {
            if ($extension->name === $package) {
                return $this->extensions->isEnabled($extension->getId());
            }
        }

        return false;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        RequestUtil::getActor($request)->assertAdmin();

        $body = (array) $request->getParsedBody();
        $packages = array_values(array_filter((array) Arr::get($body, 'packages', [])));

        /*
         * The nightly build: the server, not the browser, decides what moves
         * and to which branch. See Plan\Nightly.
         */
        $nightly = filter_var(Arr::get($body, 'nightly', false), FILTER_VALIDATE_BOOL)
            ? (new Nightly($this->paths->base.'/composer.json', $this->paths->base.'/composer.lock'))->targets()
            : null;

        if ($nightly === []) {
            return new JsonResponse(['error' => Lang::trans('start.no_nightly')], 422);
        }

        if ($nightly !== null) {
            $packages = array_keys($nightly);
        }
        $mode = in_array(Arr::get($body, 'mode'), ['install', 'remove'], true)
            ? (string) Arr::get($body, 'mode')
            : 'update';

        foreach ($packages as $package) {
            // The only user-supplied value that reaches a command line.
            if (! preg_match('#^[a-z0-9]([_.-]?[a-z0-9]+)*/[a-z0-9](([_.]|-{1,2})?[a-z0-9]+)*$#', (string) $package)) {
                return new JsonResponse(['error' => Lang::trans('start.not_a_package', ['package' => (string) $package])], 422);
            }
        }

        $capability = new Capability($this->paths->base, \Flarum\ExtensionManager\Host\PhpBinary::forStorage($this->paths->storage), $this->paths->storage.'/.composer');

        if ($packages === []) {
            return new JsonResponse(['error' => Lang::trans('start.nothing_selected')], 422);
        }

        if ($mode === 'install' && ($refusal = $this->replaced($packages)) !== null) {
            return new JsonResponse(['error' => $refusal], 422);
        }

        if ($mode === 'remove' && ($refusal = $this->cannotRemove($packages)) !== null) {
            return new JsonResponse(['error' => $refusal], 422);
        }

        if ($capability->resolveTier() === Capability::NONE) {
            return new JsonResponse([
                'error' => Lang::trans('start.not_enough_memory'),
            ], 422);
        }

        $existing = $this->runs->latest();

        if ($existing !== null && ! $existing->isFinished()) {
            /*
             * 🚨 Refused, but LOUDLY, and with the age of the thing in the way
             * so the admin can tell a working update from a dead one. The screen
             * offers to abandon it; this endpoint never decides that for them.
             */
            $age = time() - $existing->movedAt;

            return new JsonResponse([
                'error' => $age > 120
                    ? Lang::trans('start.in_progress_stale', ['minutes' => round($age / 60)])
                    : Lang::trans('start.in_progress'),
                'run' => $this->runs->present($existing),
                'stale' => $existing->isStale(time()),
            ], 409);
        }

        $id = 'r'.date('Ymd-His').'-'.bin2hex(random_bytes(3));

        (new WorkDir($this->paths->storage, $id))->create()->remember($packages, $nightly !== null ? 'update' : $mode, $nightly ?? $this->repinFor($packages, $body, $mode));

        $run = $this->runner->begin($id);

        // A worker if there is one; the page keeps polling either way.
        $queued = $this->drivers->nudge($id);

        return new JsonResponse([
            'run' => $this->runs->present($run),
            'queued' => $queued,
            'driver' => $this->drivers->describe(),
        ]);
    }

    /**
     * Which requirements this run may raise, and to what.
     *
     * 🚨 The browser asks WHETHER to raise a pin. It never says to WHAT.
     *
     * The target comes from this site's own update check, so the only version a
     * requirement can be moved to is the one the admin was actually shown on the
     * card. A version taken from the request body would be an arbitrary string
     * from a client written straight into composer.json — a short walk to a
     * downgrade, or to a package nobody chose.
     *
     * 🚨 And only where the constraint is an exact pin. Raising a range would be
     * rewriting a decision nobody asked to change.
     *
     * @param list<string> $packages
     * @param array<string,mixed> $body
     * @return array<string,string>
     */
    private function repinFor(array $packages, array $body, string $mode): array
    {
        if ($mode !== 'update' || ! filter_var(Arr::get($body, 'repin', false), FILTER_VALIDATE_BOOL)) {
            return [];
        }

        /*
         * 🚨 The rules themselves live in Plan\Repin, because the console can
         * raise a pin too and the two must never be allowed to disagree about
         * what is allowed. What stays here is the question of PERMISSION: the
         * browser asks WHETHER, and never to what.
         */
        return (new Repin(
            $this->paths->base.'/composer.json',
            StorageDir::of($this->paths->storage).'/updates.json'
        ))->targetsFor($packages);
    }
}
