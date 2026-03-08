<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\ExtensionManager\Command;

use Composer\Semver\Semver;
use Flarum\Extension\Extension;
use Flarum\Extension\ExtensionManager;
use Flarum\ExtensionManager\Composer\ComposerAdapter;
use Flarum\ExtensionManager\Exception\ComposerRequireFailedException;
use Flarum\ExtensionManager\Exception\ExtensionAlreadyInstalledException;
use Flarum\ExtensionManager\Exception\ExtensionIncompatibleWithFlarumException;
use Flarum\ExtensionManager\Extension\Event\Installed;
use Flarum\ExtensionManager\RequirePackageValidator;
use Flarum\Foundation\Application;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Collection;
use Symfony\Component\Console\Input\StringInput;

class RequireExtensionHandler
{
    public function __construct(
        protected ComposerAdapter $composer,
        protected ExtensionManager $extensions,
        protected RequirePackageValidator $validator,
        protected Dispatcher $events,
        protected Client $http,
    ) {
    }

    /**
     * @throws \Flarum\User\Exception\PermissionDeniedException
     * @throws \Exception
     */
    public function handle(RequireExtension $command): array
    {
        $command->actor->assertAdmin();

        $this->validator->assertValid(['package' => $command->package]);

        $extensionId = Extension::nameToId($command->package);
        $extension = $this->extensions->getExtension($extensionId);

        if (! empty($extension)) {
            throw new ExtensionAlreadyInstalledException($extension);
        }

        $packageName = $command->package;

        // Auto append :* if not requiring a specific version.
        if (! str_contains($packageName, ':')) {
            $packageName .= ':*';
        }

        $this->assertFlarumCompatibility($packageName);

        $output = $this->composer->run(
            new StringInput("require $packageName -W"),
            $command->task ?? null,
            true
        );

        if ($output->getExitCode() !== 0) {
            throw new ComposerRequireFailedException($packageName, $output->getContents());
        }

        $this->events->dispatch(
            new Installed($extensionId)
        );

        return ['id' => $extensionId];
    }

    /**
     * Check the package's latest stable release on Packagist to confirm it declares
     * a flarum/core constraint that is satisfied by the running Flarum version.
     *
     * Packages with "*" or other overly broad constraints pass Composer's resolver
     * but may not actually work on the current major version.
     *
     * If Packagist is unreachable or the package has no stable releases, we allow
     * the install to proceed and let Composer's own resolver be the final gate.
     *
     * @throws ExtensionIncompatibleWithFlarumException
     */
    protected function assertFlarumCompatibility(string $packageName): void
    {
        $rawName = preg_replace('/^([A-z0-9-_\/]+)(?::.*|)$/i', '$1', $packageName);

        try {
            $response = $this->http->get("https://repo.packagist.org/p2/{$rawName}.json");
        } catch (GuzzleException) {
            // If Packagist is unreachable, let Composer try — it will fail with its own error.
            return;
        }

        if ($response->getStatusCode() < 200 || $response->getStatusCode() >= 300) {
            return;
        }

        $json = json_decode($response->getBody()->getContents(), true);

        $packages = new Collection($json['packages'][$rawName] ?? []);

        if ($packages->isEmpty()) {
            return;
        }

        // Find the latest stable (non-dev) release.
        $latest = $packages->first(fn (array $p) => ! str_contains($p['version'] ?? '', 'dev-'));

        if (! $latest) {
            return;
        }

        $coreConstraint = $latest['require']['flarum/core'] ?? null;

        if (! $coreConstraint) {
            return;
        }

        // A constraint that satisfies both 1.x and 2.x (e.g. "*") is too broad
        // to trust — the extension was almost certainly written for one major version only.
        // Reject it unless it was explicitly authored for the current major.
        $tooPermissive = Semver::satisfies('1.0.0', $coreConstraint) && Semver::satisfies('2.0.0', $coreConstraint);

        if ($tooPermissive || ! Semver::satisfies(Application::VERSION, $coreConstraint)) {
            throw new ExtensionIncompatibleWithFlarumException($rawName, $coreConstraint);
        }
    }
}
