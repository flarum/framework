<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\ExtensionManager\Api\Controller;

use Flarum\Extension\ExtensionManager;
use Flarum\ExtensionManager\Discover\Cache;
use Flarum\ExtensionManager\Discover\Compatibility;
use Flarum\ExtensionManager\Discover\Packagist;
use Flarum\ExtensionManager\Exception\RefusedException;
use Flarum\ExtensionManager\Host\Capability;
use Flarum\ExtensionManager\Host\StorageDir;
use Flarum\ExtensionManager\Plan\Nightly;
use Flarum\ExtensionManager\Plan\Repin;
use Flarum\ExtensionManager\Run\Drivers;
use Flarum\ExtensionManager\Run\RunStore;
use Flarum\ExtensionManager\Run\StepRunner;
use Flarum\ExtensionManager\Work\WorkDir;
use Flarum\Foundation\Application;
use Flarum\Foundation\Paths;
use Flarum\Foundation\ValidationException;
use Flarum\Http\RequestUtil;
use Flarum\Locale\TranslatorInterface;
use GuzzleHttp\ClientInterface;
use Illuminate\Support\Arr;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Start an update.
 *
 * Every refusal here says why, in words aimed at the person reading them.
 * Refusing silently when anything looks busy is how one dead task blocks every
 * later update forever, with the admin left looking at a spinner and no
 * explanation anywhere.
 */
class StartController implements RequestHandlerInterface
{
    public function __construct(
        protected TranslatorInterface $translator,
        protected RunStore $runs,
        protected StepRunner $runner,
        protected Drivers $drivers,
        protected Paths $paths,
        protected ExtensionManager $extensions,
        protected Application $app,
        protected ClientInterface $http,
    ) {
    }

    /**
     * A replaced package is never installed under its old name, whatever
     * the screen showed. Installing v17development/flarum-seo gave forums
     * FoF SEO under the wrong id, which then refused to enable
     * (discuss.flarum.org d/40012). Asked here, on the server, so a stale tab
     * or a hand-made request cannot get round it; the verdict is the cached one
     * the Find tab already fetched, so this costs nothing in the usual case.
     *
     * @param list<string> $packages
     */
    protected function replaced(array $packages): ?string
    {
        $packagist = new Packagist($this->translator, new Cache(StorageDir::of($this->paths->storage).'/packagist'), $this->http);
        $verdicts = $packagist->verdicts(array_slice($packages, 0, 5), new Compatibility(ltrim($this->app->version(), 'v')));

        foreach ($verdicts as $name => $verdict) {
            if (! empty($verdict['replaced'])) {
                return $verdict['replacedBy']
                    ? $this->translator->trans('flarum-extension-manager.api.start.replaced_by', ['name' => $name, 'replacement' => $verdict['replacedBy']])
                    : $this->translator->trans('flarum-extension-manager.api.start.replaced', ['name' => $name]);
            }
        }

        return null;
    }

    /**
     * Why these packages cannot be removed, if they cannot.
     *
     * @param list<string> $packages
     */
    protected function cannotRemove(array $packages): ?string
    {
        $json = (array) json_decode((string) @file_get_contents($this->paths->base.'/composer.json'), true);
        $direct = array_merge((array) ($json['require'] ?? []), (array) ($json['require-dev'] ?? []));

        foreach ($packages as $package) {
            /*
             * Only what this site asked for itself: `composer remove` on something this site never asked for
             * fails with a message about a package not being required, which
             * reads like a bug. An extension that arrived as somebody else's
             * dependency goes when that dependency goes, and not before.
             */
            if (! isset($direct[$package])) {
                return $this->translator->trans('flarum-extension-manager.api.start.not_direct', ['package' => $package]);
            }

            /*
             * Disabled first, and the reason is ordering: taking the files away from an extension that is still
             * switched on leaves Flarum with an enabled extension it cannot
             * load. Disabling first is one click, and it is the click that makes
             * the removal safe.
             */
            if ($this->isEnabled($package)) {
                return $this->translator->trans('flarum-extension-manager.api.start.still_enabled', ['package' => $package]);
            }
        }

        return null;
    }

    protected function isEnabled(string $package): bool
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
            ? (new Nightly($this->paths->base.'/composer.json', $this->paths->base.'/composer.lock', $this->http))->targets()
            : null;

        if ($nightly === []) {
            throw new RefusedException($this->translator->trans('flarum-extension-manager.api.start.no_nightly'), 'nothing_to_update');
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
                throw new ValidationException(['packages' => $this->translator->trans('flarum-extension-manager.api.start.not_a_package', ['package' => (string) $package])]);
            }
        }

        $capability = new Capability($this->translator, $this->paths->base, \Flarum\ExtensionManager\Host\PhpBinary::forStorage($this->translator, $this->paths->storage), $this->paths->storage.'/.composer');

        if ($packages === []) {
            throw new ValidationException(['packages' => $this->translator->trans('flarum-extension-manager.api.start.nothing_selected')]);
        }

        if ($mode === 'install' && ($refusal = $this->replaced($packages)) !== null) {
            throw new RefusedException($refusal, 'package_replaced');
        }

        if ($mode === 'remove' && ($refusal = $this->cannotRemove($packages)) !== null) {
            throw new RefusedException($refusal, 'cannot_remove_package');
        }

        if ($capability->resolveTier() === Capability::NONE) {
            throw new RefusedException($this->translator->trans('flarum-extension-manager.api.start.not_enough_memory'), 'not_enough_memory');
        }

        $existing = $this->runs->latest();

        if ($existing !== null && ! $existing->isFinished()) {
            /*
             * Refused, but loudly, and with the age of the thing in the way so
             * the admin can tell a working update from a dead one. The screen
             * reloads the state, which carries the run, and offers to abandon
             * it; this endpoint never decides that for them.
             */
            $age = time() - $existing->movedAt;

            throw new RefusedException($age > 120
                ? $this->translator->trans('flarum-extension-manager.api.start.in_progress_stale', ['minutes' => round($age / 60)])
                : $this->translator->trans('flarum-extension-manager.api.start.in_progress'), 'run_in_progress', 409);
        }

        $id = 'r'.date('Ymd-His').'-'.bin2hex(random_bytes(3));

        (new WorkDir($this->translator, $this->paths->storage, $id))->create()->remember($packages, $nightly !== null ? 'update' : $mode, $nightly ?? $this->repinFor($packages, $body, $mode));

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
     * The browser asks WHETHER to raise a pin. It never says to WHAT.
     *
     * The target comes from this site's own update check, so the only version a
     * requirement can be moved to is the one the admin was actually shown on the
     * card. A version taken from the request body would be an arbitrary string
     * from a client written straight into composer.json — a short walk to a
     * downgrade, or to a package nobody chose.
     *
     * And only where the constraint is an exact pin. Raising a range would be
     * rewriting a decision nobody asked to change.
     *
     * @param list<string> $packages
     * @param array<string,mixed> $body
     * @return array<string,string>
     */
    protected function repinFor(array $packages, array $body, string $mode): array
    {
        if ($mode !== 'update' || ! filter_var(Arr::get($body, 'repin', false), FILTER_VALIDATE_BOOL)) {
            return [];
        }

        /*
         * The rules themselves live in Plan\Repin, because the console can
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
