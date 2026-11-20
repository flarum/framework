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

    #[Test]
    public function extracts_commit_hash_for_dev_versions_only(): void
    {
        $extensions = $this->manager(json_encode(['packages' => [
            [
                'name' => 'acme/dev-dist',
                'type' => 'flarum-extension',
                'version' => '2.x-dev',
                'dist' => ['reference' => '9AE9C2B5222172709D6E811DA39B6A5BF588E198', 'url' => 'https://token:secret@example.com/archive.zip'],
                'extra' => ['flarum-extension' => ['title' => 'Dev Dist']],
            ],
            [
                'name' => 'acme/dev-source',
                'type' => 'flarum-extension',
                'version' => 'dev-main',
                'source' => ['reference' => 'abcdef1234567890abcdef1234567890abcdef12'],
                'extra' => ['flarum-extension' => ['title' => 'Dev Source']],
            ],
            [
                'name' => 'acme/stable',
                'type' => 'flarum-extension',
                'version' => '2.0.3',
                'dist' => ['reference' => '1111111111111111111111111111111111111111'],
                'extra' => ['flarum-extension' => ['title' => 'Stable']],
            ],
        ]]))->getExtensions();

        $this->assertSame('9ae9c2b5222172709d6e811da39b6a5bf588e198', $extensions->get('acme-dev-dist')->getCommit());
        $this->assertSame('9ae9c2b5222172709d6e811da39b6a5bf588e198', $extensions->get('acme-dev-dist')->toArray()['commit']);
        $this->assertSame('abcdef1234567890abcdef1234567890abcdef12', $extensions->get('acme-dev-source')->getCommit());
        $this->assertNull($extensions->get('acme-stable')->getCommit());
    }

    #[Test]
    public function rejects_non_hex_or_injected_commit_references(): void
    {
        $extensions = $this->manager(json_encode(['packages' => [
            [
                'name' => 'acme/xss',
                'type' => 'flarum-extension',
                'version' => 'dev-main',
                'commit' => '<script>alert(1)</script>',
                'dist' => ['reference' => '<error>injected</error>'],
                'extra' => ['flarum-extension' => ['title' => 'XSS']],
            ],
            [
                'name' => 'acme/too-short',
                'type' => 'flarum-extension',
                'version' => '2.x-dev',
                'source' => ['reference' => 'abc123'],
                'extra' => ['flarum-extension' => ['title' => 'Too Short']],
            ],
        ]]))->getExtensions();

        $this->assertNull($extensions->get('acme-xss')->getCommit());
        $this->assertNull($extensions->get('acme-xss')->toArray()['commit']);
        $this->assertNull($extensions->get('acme-too-short')->getCommit());
    }

    #[Test]
    public function subextensions_inherit_parent_version_and_commit_without_leaking_parent_urls(): void
    {
        $subDir = $this->vendor.'/acme/monorepo/extensions/child';
        (new Filesystem())->makeDirectory($subDir, 0777, true);
        file_put_contents($subDir.'/composer.json', json_encode([
            'name' => 'acme/child',
            'type' => 'flarum-extension',
            'extra' => ['flarum-extension' => ['title' => 'Child']],
        ]));

        $extensions = $this->manager(json_encode(['packages' => [
            [
                'name' => 'acme/monorepo',
                'type' => 'library',
                'version' => '2.x-dev',
                'dist' => [
                    'reference' => 'deadbeef1234567890abcdef1234567890abcdef',
                    'url' => 'https://x-access-token:ghp_secret@github.com/acme/monorepo.zip',
                ],
                'source' => [
                    'url' => 'https://x-access-token:ghp_secret@github.com/acme/monorepo.git',
                ],
                'extra' => [
                    'flarum-subextensions' => ['extensions/child', '../escaped-path'],
                ],
            ],
        ]]))->getExtensions();

        $child = $extensions->get('acme-child');
        $this->assertNotNull($child);
        $this->assertSame('2.x-dev', $child->getVersion());
        $this->assertSame('deadbeef1234567890abcdef1234567890abcdef', $child->getCommit());
        $this->assertArrayNotHasKey('url', $child->toArray()['dist'] ?? []);
        $this->assertArrayNotHasKey('source', $child->toArray());
    }
}
