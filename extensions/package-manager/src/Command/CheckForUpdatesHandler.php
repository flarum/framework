<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\ExtensionManager\Command;

use Composer\Semver\Semver;
use Flarum\Extension\ExtensionManager;
use Flarum\ExtensionManager\Composer\ComposerAdapter;
use Flarum\ExtensionManager\Composer\ComposerJson;
use Flarum\ExtensionManager\Exception\ComposerCommandFailedException;
use Flarum\ExtensionManager\Settings\LastUpdateCheck;
use Flarum\ExtensionManager\Support\Util;
use Flarum\Foundation\Application;
use GuzzleHttp\Client;
use Illuminate\Support\Collection;
use Symfony\Component\Console\Input\ArrayInput;

class CheckForUpdatesHandler
{
    /**
     * @var ComposerAdapter
     */
    protected $composer;

    /**
     * @var \Flarum\ExtensionManager\Settings\LastUpdateCheck
     */
    protected $lastUpdateCheck;

    /**
     * @var ExtensionManager
     */
    protected $extensions;

    /**
     * @var ComposerJson
     */
    protected $composerJson;

    /**
     * @var Client
     */
    protected $http;

    protected $meta = [];

    public function __construct(ComposerAdapter $composer, LastUpdateCheck $lastUpdateCheck, ExtensionManager $extensions, ComposerJson $composerJson, Client $http)
    {
        $this->composer = $composer;
        $this->lastUpdateCheck = $lastUpdateCheck;
        $this->extensions = $extensions;
        $this->composerJson = $composerJson;
        $this->http = $http;
    }

    /**
     * We run two commands here.
     *
     * `composer outdated -D --format json`
     * This queries latest versions for all direct packages, so it can include major updates,
     * that are not necessarily compatible with the current flarum version.
     * That includes flarum/core itself, so for example if we are on flarum/core v1.8.0
     * and there are v1.8.1 and v2.0.0 available, the command would only let us know of v2.0.0.
     *
     * `composer outdated -D --minor-only --format json`
     * This only lists latest minor updates, we need to run this as well not only to be able to know
     * of these minor versions in addition to major ones, but especially for the flarum/core, as explained above
     * we need to know of minor core updates, even if there is a major version available.
     *
     * The results from both commands are properly processed and merged to have new key values `latest-minor` and `latest-major`.
     *
     * @throws ComposerCommandFailedException
     */
    public function handle(CheckForUpdates $command)
    {
        $firstOutput = $this->runComposerCommand(false, $command);
        $firstOutput = json_decode($this->cleanJson($firstOutput), true);

        $installed = new Collection($firstOutput['installed'] ?? []);
        $majorUpdates = $installed->contains(function (array $package) {
            return isset($package['latest-status']) && $package['latest-status'] === 'update-possible' && Util::isMajorUpdate($package['version'], $package['latest']);
        });

        if ($majorUpdates) {
            $secondOutput = $this->runComposerCommand(true, $command);
            $secondOutput = json_decode($this->cleanJson($secondOutput), true);
        }

        if (! isset($secondOutput)) {
            $secondOutput = ['installed' => []];
        }

        $updates = new Collection();
        $composerJson = $this->composerJson->get();

        foreach ($installed as $mainPackageUpdate) {
            // Skip if not an extension
            if (! $this->extensions->getExtension(Util::nameToId($mainPackageUpdate['name']))) {
                continue;
            }

            $mainPackageUpdate['latest-minor'] = $mainPackageUpdate['latest-major'] = null;

            if ($mainPackageUpdate['latest-status'] === 'up-to-date' && Util::isMajorUpdate($mainPackageUpdate['version'], $mainPackageUpdate['latest'])) {
                continue;
            }

            if (isset($mainPackageUpdate['latest-status']) && $mainPackageUpdate['latest-status'] === 'update-possible' && Util::isMajorUpdate($mainPackageUpdate['version'], $mainPackageUpdate['latest'])) {
                $mainPackageUpdate['latest-major'] = $mainPackageUpdate['latest'];

                $minorPackageUpdate = array_filter($secondOutput['installed'], function ($package) use ($mainPackageUpdate) {
                    return $package['name'] === $mainPackageUpdate['name'];
                })[0] ?? null;

                if ($minorPackageUpdate) {
                    $mainPackageUpdate['latest-minor'] = $minorPackageUpdate['latest'];
                }
            } else {
                $mainPackageUpdate['latest-minor'] = $mainPackageUpdate['latest'] ?? null;
            }

            $mainPackageUpdate['required-as'] = $composerJson['require'][$mainPackageUpdate['name']] ?? null;

            if (! $this->compatibleWithCurrentFlarumVersion($mainPackageUpdate)) {
                continue;
            }

            $updates->push($mainPackageUpdate);
        }

        return $this->lastUpdateCheck
            ->with('installed', $updates->values()->toArray())
            ->save();
    }

    /**
     * Composer can sometimes return text above the JSON.
     * This method tries to remove such occurences.
     */
    protected function cleanJson(string $composerOutput): string
    {
        return preg_replace('/^[^{]+\n({.*)/ms', '$1', $composerOutput);
    }

    /**
     * @throws ComposerCommandFailedException
     */
    protected function runComposerCommand(bool $minorOnly, CheckForUpdates $command): string
    {
        $input = [
            'command' => 'outdated',
            '--format' => 'json',
        ];

        if ($minorOnly) {
            $input['--minor-only'] = true;
        }

        $output = $this->composer->run(new ArrayInput($input), $command->task ?? null);

        if ($output->getExitCode() !== 0) {
            throw new ComposerCommandFailedException('', $output->getContents());
        }

        return $output->getContents();
    }

    private function compatibleWithCurrentFlarumVersion(array $mainPackageUpdate): bool
    {
        if (empty($mainPackageUpdate['latest-major']) || str_contains($mainPackageUpdate['latest-major'], 'dev-')) {
            return true;
        }

        if (! empty($this->meta[$mainPackageUpdate['name']])) {
            $json = $this->meta[$mainPackageUpdate['name']];
        } else {
            $response = $this->http->get("https://repo.packagist.org/p2/{$mainPackageUpdate['name']}.json");

            $body = $response->getBody()->getContents();

            if ($response->getStatusCode() > 299 || $response->getStatusCode() < 200) {
                return true;
            }

            $json = json_decode($body, true);

            $this->meta[$mainPackageUpdate['name']] = $json;
        }

        $packages = Collection::make($json['packages'][$mainPackageUpdate['name']] ?? []);

        if ($packages->isEmpty()) {
            return true;
        }

        $package = $packages->firstWhere('version', $mainPackageUpdate['latest-major']);

        if (! $package) {
            return true;
        }

        $flarumVersion = Application::VERSION;

        $require = $package['require']['flarum/core'] ?? null;

        if (! $require || str_contains($require, 'dev-')) {
            return true;
        }

        return Semver::satisfies($flarumVersion, $require);
    }
}
