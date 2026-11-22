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
    public function extracts_sanitized_commit_hash_for_dev_versions_only(): void
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
                'name' => 'acme/dev-uppercase',
                'type' => 'flarum-extension',
                'version' => '2.0.0-DEV',
                'version_normalized' => '2.0.0.0-dev',
                'dist' => ['reference' => '7777777777777777777777777777777777777777'],
                'extra' => ['flarum-extension' => ['title' => 'Dev Uppercase']],
            ],
            [
                'name' => 'acme/stable',
                'type' => 'flarum-extension',
                'version' => '2.0.3',
                'dist' => ['reference' => '1111111111111111111111111111111111111111'],
                'extra' => ['flarum-extension' => ['title' => 'Stable']],
            ],
            [
                'name' => 'acme/xss',
                'type' => 'flarum-extension',
                'version' => 'dev-main',
                'commit' => '<script>alert(1)</script>',
                'dist' => ['reference' => '<error>injected</error>'],
                'extra' => ['flarum-extension' => ['title' => 'XSS']],
            ],
            [
                'name' => 'acme/trailing-newline',
                'type' => 'flarum-extension',
                'version' => 'dev-main',
                'dist' => ['reference' => "abcdef1\n"],
                'extra' => ['flarum-extension' => ['title' => 'Trailing Newline']],
            ],
            [
                'name' => 'acme/too-short',
                'type' => 'flarum-extension',
                'version' => '2.x-dev',
                'source' => ['reference' => 'abc123'],
                'extra' => ['flarum-extension' => ['title' => 'Too Short']],
            ],
            // Covers both config-hash references and stale install-time .git references on path repos.
            [
                'name' => 'acme/path',
                'type' => 'flarum-extension',
                'version' => 'dev-main',
                'installation-source' => 'dist',
                'dist' => ['type' => 'path', 'url' => '../path', 'reference' => '0123456789abcdef0123456789abcdef01234567'],
                'extra' => ['flarum-extension' => ['title' => 'Path']],
            ],
            [
                'name' => 'acme/from-source',
                'type' => 'flarum-extension',
                'version' => '2.x-dev',
                'installation-source' => 'source',
                'dist' => ['type' => 'zip', 'reference' => '1111111111111111111111111111111111111111'],
                'source' => ['type' => 'git', 'reference' => '2222222222222222222222222222222222222222'],
                'extra' => ['flarum-extension' => ['title' => 'From Source']],
            ],
            [
                'name' => 'acme/dist-non-hex-fallback',
                'type' => 'flarum-extension',
                'version' => '2.x-dev',
                'installation-source' => 'dist',
                'dist' => ['type' => 'zip', 'reference' => 'not-a-hex-sha'],
                'source' => ['type' => 'git', 'reference' => '3333333333333333333333333333333333333333'],
                'extra' => ['flarum-extension' => ['title' => 'Dist Fallback']],
            ],
            [
                'name' => 'acme/source-missing-ref',
                'type' => 'flarum-extension',
                'version' => '2.x-dev',
                'installation-source' => 'source',
                'dist' => ['type' => 'zip', 'reference' => '4444444444444444444444444444444444444444'],
                'extra' => ['flarum-extension' => ['title' => 'Source Missing Ref']],
            ],
        ]]))->getExtensions();

        $this->assertSame('9ae9c2b5222172709d6e811da39b6a5bf588e198', $extensions->get('acme-dev-dist')->getCommit());
        $this->assertSame('9ae9c2b5222172709d6e811da39b6a5bf588e198', $extensions->get('acme-dev-dist')->toArray()['commit']);
        $this->assertSame('abcdef1234567890abcdef1234567890abcdef12', $extensions->get('acme-dev-source')->getCommit());
        $this->assertSame('7777777777777777777777777777777777777777', $extensions->get('acme-dev-uppercase')->getCommit());
        $this->assertNull($extensions->get('acme-stable')->getCommit());
        $this->assertNull($extensions->get('acme-xss')->getCommit());
        $this->assertNull($extensions->get('acme-xss')->toArray()['commit']);
        $this->assertNull($extensions->get('acme-trailing-newline')->getCommit());
        $this->assertNull($extensions->get('acme-too-short')->getCommit());
        $this->assertNull($extensions->get('acme-path')->getCommit());
        $this->assertSame('2222222222222222222222222222222222222222', $extensions->get('acme-from-source')->getCommit());
        $this->assertSame('3333333333333333333333333333333333333333', $extensions->get('acme-dist-non-hex-fallback')->getCommit());
        $this->assertNull($extensions->get('acme-source-missing-ref')->getCommit());
    }

    #[Test]
    public function subextensions_inherit_parent_version_and_commit_without_leaking_parent_urls(): void
    {
        $fs = new Filesystem();
        $subDirs = [
            $this->vendor.'/acme/monorepo/extensions/child' => ['name' => 'acme/child', 'title' => 'Child'],
            $this->vendor.'/acme/monorepo-path/extensions/child' => ['name' => 'acme/path-child', 'title' => 'Path Child'],
            $this->vendor.'/acme/monorepo-source/extensions/child' => ['name' => 'acme/source-child', 'title' => 'Source Child'],
        ];

        foreach ($subDirs as $dir => $meta) {
            $fs->makeDirectory($dir, 0777, true);
            file_put_contents($dir.'/composer.json', json_encode([
                'name' => $meta['name'],
                'type' => 'flarum-extension',
                'extra' => ['flarum-extension' => ['title' => $meta['title']]],
            ]));
        }

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
                    'flarum-subextensions' => ['extensions/child'],
                ],
            ],
            [
                'name' => 'acme/monorepo-path',
                'type' => 'library',
                'version' => '2.x-dev',
                'installation-source' => 'dist',
                'dist' => ['type' => 'path', 'url' => '../monorepo-path', 'reference' => 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa'],
                'extra' => ['flarum-subextensions' => ['extensions/child']],
            ],
            [
                'name' => 'acme/monorepo-source',
                'type' => 'library',
                'version' => '2.0.0-DEV',
                'version_normalized' => '2.0.0.0-dev',
                'installation-source' => 'source',
                'dist' => ['type' => 'zip', 'reference' => '5555555555555555555555555555555555555555'],
                'source' => ['type' => 'git', 'reference' => '6666666666666666666666666666666666666666'],
                'extra' => ['flarum-subextensions' => ['extensions/child']],
            ],
        ]]))->getExtensions();

        $child = $extensions->get('acme-child');
        $this->assertNotNull($child);
        $this->assertSame('2.x-dev', $child->getVersion());
        $this->assertSame('deadbeef1234567890abcdef1234567890abcdef', $child->getCommit());
        $this->assertArrayNotHasKey('url', $child->toArray()['dist'] ?? []);
        $this->assertArrayNotHasKey('source', $child->toArray());
        $this->assertNull($extensions->get('acme-path-child')->getCommit());
        $this->assertSame('6666666666666666666666666666666666666666', $extensions->get('acme-source-child')->getCommit());
    }
}
