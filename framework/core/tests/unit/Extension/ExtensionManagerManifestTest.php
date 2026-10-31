<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\unit\Extension;

use Flarum\Database\Migrator;
use Flarum\Extension\Exception\UnreadableManifestException;
use Flarum\Extension\ExtensionManager;
use Flarum\Foundation\MaintenanceMode;
use Flarum\Foundation\Paths;
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\Testing\unit\TestCase;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Filesystem\Filesystem;
use PHPUnit\Framework\Attributes\Test;

class ExtensionManagerManifestTest extends TestCase
{
    private string $vendor;

    /**
     * Every `extensions_enabled` write the manager makes, so a test can assert that the
     * setting was left alone.
     */
    private array $writes = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->vendor = sys_get_temp_dir().'/flarum-manifest-test-'.bin2hex(random_bytes(6)).'/vendor';

        (new Filesystem())->makeDirectory($this->vendor.'/composer', 0777, true);
    }

    protected function tearDown(): void
    {
        (new Filesystem())->deleteDirectory(dirname($this->vendor));

        parent::tearDown();
    }

    private function manager(?string $manifest): ExtensionManager
    {
        $path = $this->vendor.'/composer/installed.json';

        if ($manifest === null) {
            @unlink($path);
        } else {
            file_put_contents($path, $manifest);
        }

        $settings = $this->createStub(SettingsRepositoryInterface::class);
        $settings->method('get')->willReturnCallback(function (string $key) {
            return $key === 'extensions_enabled' ? '["flarum-tags","flarum-markdown"]' : null;
        });
        $settings->method('set')->willReturnCallback(function (string $key, $value) {
            $this->writes[] = [$key, $value];
        });

        return new ExtensionManager(
            $settings,
            new Paths([
                'base' => dirname($this->vendor),
                'public' => dirname($this->vendor),
                'storage' => dirname($this->vendor),
                'vendor' => $this->vendor,
            ]),
            $this->createStub(Container::class),
            $this->createStub(Migrator::class),
            $this->createStub(Dispatcher::class),
            new Filesystem(),
            $this->createStub(MaintenanceMode::class),
        );
    }

    #[Test]
    public function throws_when_the_manifest_is_missing(): void
    {
        $this->expectException(UnreadableManifestException::class);

        $this->manager(null)->getExtensions();
    }

    #[Test]
    public function throws_when_the_manifest_is_not_valid_json(): void
    {
        $this->expectException(UnreadableManifestException::class);

        $this->manager('{"packages":[{"name":"flarum/tags","type":"flarum-ext')->getExtensions();
    }

    /**
     * A manifest that cannot be parsed says nothing about which extensions are installed.
     * Treating it as an empty list makes the manager prune every enabled extension and
     * persist that, so an interrupted composer run would silently disable the forum.
     */
    #[Test]
    public function does_not_touch_enabled_extensions_when_the_manifest_cannot_be_read(): void
    {
        foreach ([null, '{"packages":[{"name":"flarum/tags","type":"flarum-ext', 'not json at all'] as $manifest) {
            $this->writes = [];

            try {
                $this->manager($manifest)->getExtensions();
            } catch (UnreadableManifestException $e) {
                // expected
            }

            $this->assertSame([], $this->writes, 'Expected no settings write for manifest: '.var_export($manifest, true));
        }
    }

    /**
     * A manifest that parses is authoritative. If it lists no extensions then the enabled
     * ones really are gone, and pruning them is the existing self-healing behaviour.
     */
    #[Test]
    public function prunes_enabled_extensions_when_the_manifest_is_readable_and_empty(): void
    {
        $extensions = $this->manager('{"packages":[]}')->getExtensions();

        $this->assertCount(0, $extensions);
        $this->assertSame([['extensions_enabled', '[]']], $this->writes);
    }

    #[Test]
    public function reads_extensions_from_a_valid_manifest(): void
    {
        $extensions = $this->manager(json_encode(['packages' => [
            [
                'name' => 'flarum/tags',
                'type' => 'flarum-extension',
                'extra' => ['flarum-extension' => ['title' => 'Tags']],
            ],
            [
                'name' => 'psr/log',
                'type' => 'library',
            ],
        ]]))->getExtensions();

        $this->assertCount(1, $extensions, 'Only flarum-extension packages are extensions');
        $this->assertNotNull($extensions->get('flarum-tags'));
    }
}
