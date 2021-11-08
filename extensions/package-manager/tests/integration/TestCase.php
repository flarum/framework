<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\PackageManager\Tests\integration;

use Composer\Config;
use Composer\Console\Application;
use Flarum\Extension\ExtensionManager;
use Flarum\Foundation\Paths;
use Flarum\PackageManager\Extension\ExtensionUtils;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Arr;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\NullOutput;

class TestCase extends \Flarum\Testing\integration\TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-package-manager', 'flarum-tags');

        $tmp = $this->tmpDir();

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
        /** @var Application $composer */
        $composer = $this->app()->getContainer()->make(Application::class);
        $output = new NullOutput();
        $composer->run(new StringInput($command), $output);
    }

    protected function guessedCause(ResponseInterface $response): ?string
    {
        $json = json_decode($response->getBody()->getContents(), true);

        return $json['errors'] ? ($json['errors'][0]['guessed_cause'] ?? null) : null;
    }
}
