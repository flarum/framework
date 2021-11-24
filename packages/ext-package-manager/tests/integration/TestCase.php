<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\PackageManager\Tests\integration;

use Flarum\Foundation\Paths;
use Flarum\PackageManager\Composer\ComposerAdapter;
use Flarum\PackageManager\Composer\ComposerJson;
use Flarum\PackageManager\Extension\ExtensionUtils;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Illuminate\Support\Arr;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Console\Input\StringInput;

class TestCase extends \Flarum\Testing\integration\TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-package-manager', 'flarum-tags');

        $tmp = realpath($this->tmpDir());

        $this->app()->getContainer()->instance('flarum.paths', new Paths([
            'base' => $tmp,
            'public' => "$tmp/public",
            'storage' => "$tmp/storage",
            'vendor' => "$tmp/vendor",
        ]));
    }

    protected function assertExtension(string $id, bool $exists)
    {
        $installed = json_decode(file_get_contents($this->app()->getContainer()->make(Paths::class)->vendor.'/composer/installed.json'), true);
        $installedExtensions = Arr::where($installed['packages'] ?? $installed, function (array $package) {
            return $package['type'] === 'flarum-extension';
        });
        $installedExtensionIds = array_map(function (string $name) {
            return ExtensionUtils::nameToId($name);
        }, Arr::pluck($installedExtensions, 'name'));

        if ($exists) {
            $this->assertTrue(in_array($id, $installedExtensionIds), "Failed asserting that extension $id is installed");
        } else {
            $this->assertFalse(in_array($id, $installedExtensionIds), "Failed asserting that extension $id is not installed");
        }
    }

    protected function assertExtensionExists(string $id)
    {
        $this->assertExtension($id, true);
    }

    protected function assertExtensionNotExists(string $id)
    {
        $this->assertExtension($id, false);
    }

    protected function assertPackageVersion(string $packageName, string $version)
    {
        $composerJson = $this->app()->getContainer()->make(ComposerJson::class)->get();

        $this->assertArrayHasKey($packageName, $composerJson['require'], "$packageName is not required.");
        $this->assertEquals($version, $composerJson['require'][$packageName], "Expected $packageName to be $version, found {$composerJson['require'][$packageName]} instead.");
    }

    protected function requireExtension(string $package)
    {
        $this->composer("require $package");
    }

    protected function removeExtension(string $package)
    {
        $this->composer("remove $package");
    }

    protected function composer(string $command): void
    {
        /** @var ComposerAdapter $composer */
        $composer = $this->app()->getContainer()->make(ComposerAdapter::class);
        $composer->run(new StringInput($command));
    }

    protected function errorGuessedCause(ResponseInterface $response): ?string
    {
        $details = $this->errorDetails($response);

        return $details['guessed_cause'] ?? null;
    }

    protected function errorDetails(ResponseInterface $response): array
    {
        $json = json_decode((string) $response->getBody(), true);

        return $json['errors'] ? ($json['errors'][0] ?? []) : [];
    }
}
