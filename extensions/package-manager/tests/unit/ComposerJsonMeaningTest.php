<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\ExtensionManager\Tests\Unit;

use Composer\Json\JsonFile as ComposerJsonFile;
use Flarum\ExtensionManager\Config\JsonFile;
use Flarum\ExtensionManager\Config\Repositories;
use Flarum\ExtensionManager\Exception\RefusedException;
use Flarum\ExtensionManager\Tests\unit\EnglishTranslator;
use Flarum\ExtensionManager\Work\ComposerSteps;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * An edit to composer.json changes what it was asked to change, and nothing
 * else about what the file means to Composer.
 *
 * Decoded into PHP arrays, `{}` came back as `[]`, so an empty `config` was
 * written as a list, which Composer's own schema refuses. Adding or removing a
 * repository renumbered the `repositories` map, turning `"packagist.org":
 * false` into a bare `false` that switches nothing off. And removing one
 * repository removed every entry that looked the same once redacted.
 */
class ComposerJsonMeaningTest extends TestCase
{
    private string $dir;

    protected function setUp(): void
    {
        $this->dir = sys_get_temp_dir().'/mw-meaning-'.bin2hex(random_bytes(4));
        mkdir($this->dir, 0775, true);
    }

    protected function tearDown(): void
    {
        exec('rm -rf '.escapeshellarg($this->dir));
    }

    // ── {} stays {} ──────────────────────────────────────────────────────────

    public function test_empty_objects_are_still_objects_after_an_edit(): void
    {
        $this->write('{"name": "a/site", "require": {}, "config": {}, "extra": {"flarum-extension": {}}, "scripts": {}, "autoload": {}, "keywords": []}');

        $file = $this->json();
        $data = $file->read();
        $data['minimum-stability'] = 'beta';
        $file->write($data);

        $raw = (string) file_get_contents($this->dir.'/composer.json');
        $after = json_decode($raw);

        foreach (['require', 'config', 'scripts', 'autoload'] as $key) {
            $this->assertInstanceOf(\stdClass::class, $after->$key, "$key must stay an object");
        }

        $this->assertInstanceOf(\stdClass::class, $after->extra->{'flarum-extension'}, 'Nested objects too.');
        $this->assertSame([], $after->keywords, 'A list stays a list.');
        $this->assertSame('beta', $after->{'minimum-stability'});
        $this->assertComposerAccepts();
    }

    /** A key the schema says is an object, written empty by our own code, is written as one. */
    public function test_a_schema_object_written_fresh_is_an_object(): void
    {
        $this->json()->write(['name' => 'a/site', 'config' => [], 'require' => []]);

        $after = json_decode((string) file_get_contents($this->dir.'/composer.json'));

        $this->assertInstanceOf(\stdClass::class, $after->config);
        $this->assertInstanceOf(\stdClass::class, $after->require);
        $this->assertComposerAccepts();
    }

    /** The whole file is an object even when there is nothing in it. */
    public function test_an_empty_file_is_written_as_an_object(): void
    {
        $this->json()->write([]);

        $this->assertSame("{}\n", file_get_contents($this->dir.'/composer.json'));
    }

    /** A second edit through the same instance remembers what the first one wrote. */
    public function test_a_second_write_from_the_same_instance_keeps_the_objects(): void
    {
        $this->write('{"name": "a/site", "extra": {"x": {}}}');

        $file = $this->json();
        $data = $file->read();
        $file->write($data);
        $file->write($data);

        $this->assertInstanceOf(\stdClass::class, json_decode((string) file_get_contents($this->dir.'/composer.json'))->extra->x);
    }

    // ── repositories keep their keys ─────────────────────────────────────────

    public function test_adding_a_repository_keeps_packagist_switched_off(): void
    {
        $this->write('{"name": "a/site", "repositories": {"packagist.org": false, "mine": {"type": "vcs", "url": "https://git.example.test/mine.git"}}}');

        $this->repositories()->add('composer', 'https://repo.example.test');

        $repos = json_decode((string) file_get_contents($this->dir.'/composer.json'), true)['repositories'];

        $this->assertFalse($repos['packagist.org'], 'Packagist was switched back on.');
        $this->assertSame('https://git.example.test/mine.git', $repos['mine']['url']);
        $this->assertSame('https://repo.example.test', end($repos)['url'], 'New entries still go last.');
        $this->assertComposerAccepts();
    }

    public function test_removing_a_repository_keeps_packagist_switched_off(): void
    {
        $this->write('{"name": "a/site", "repositories": {"packagist.org": false, "mine": {"type": "vcs", "url": "https://git.example.test/mine.git"}, "other": {"type": "vcs", "url": "https://git.example.test/other.git"}}}');

        $this->repositories()->remove('https://git.example.test/mine.git');

        $repos = json_decode((string) file_get_contents($this->dir.'/composer.json'), true)['repositories'];

        $this->assertSame(['packagist.org', 'other'], array_keys($repos));
        $this->assertFalse($repos['packagist.org']);
        $this->assertComposerAccepts();
    }

    /** The list form says it the other way, and stays a list. */
    public function test_a_list_of_repositories_stays_a_list(): void
    {
        $this->write('{"name": "a/site", "repositories": [{"packagist.org": false}, {"type": "vcs", "url": "https://git.example.test/a.git"}, {"type": "vcs", "url": "https://git.example.test/b.git"}]}');

        $this->repositories()->remove('https://git.example.test/a.git');
        $this->repositories()->add('vcs', 'https://git.example.test/c.git');

        $raw = (string) file_get_contents($this->dir.'/composer.json');
        $repos = json_decode($raw, true)['repositories'];

        $this->assertTrue(array_is_list($repos), $raw);
        $this->assertSame(['packagist.org' => false], $repos[0]);
        $this->assertSame(['https://git.example.test/b.git', 'https://git.example.test/c.git'], [$repos[1]['url'], $repos[2]['url']]);
        $this->assertComposerAccepts();
    }

    // ── removing removes ONE ─────────────────────────────────────────────────

    /**
     * Two entries that differ only in their credentials look the same
     * redacted, and both went. Given the real address, only that one goes.
     */
    public function test_the_real_address_removes_exactly_that_entry(): void
    {
        $this->write($this->twoTokens());

        $this->repositories()->remove('https://alice:token-a@git.example.test/shared.git');

        $repos = json_decode((string) file_get_contents($this->dir.'/composer.json'), true)['repositories'];

        $this->assertCount(1, $repos);
        $this->assertSame('https://bob:token-b@git.example.test/shared.git', $repos[0]['url']);
    }

    /** The screen has only the redacted form, and here that does not say which. */
    public function test_a_redacted_address_that_names_two_entries_is_refused_and_removes_neither(): void
    {
        $this->write($this->twoTokens());
        $before = (string) file_get_contents($this->dir.'/composer.json');

        try {
            $this->repositories()->remove('https://***@git.example.test/shared.git');
            $this->fail('Removing by an address that names two entries was allowed.');
        } catch (RefusedException $e) {
            $this->assertStringContainsString('differing only in the credentials', $e->getMessage());
        }

        $this->assertSame($before, file_get_contents($this->dir.'/composer.json'));
    }

    /** Where the redacted form names one entry, as it nearly always does, the screen can still remove it. */
    public function test_a_redacted_address_that_names_one_entry_removes_it(): void
    {
        $this->write('{"repositories": [{"type": "vcs", "url": "https://alice:token-a@git.example.test/shared.git"}, {"type": "vcs", "url": "https://git.example.test/open.git"}]}');

        $this->repositories()->remove('https://***@git.example.test/shared.git');

        $repos = json_decode((string) file_get_contents($this->dir.'/composer.json'), true)['repositories'];

        $this->assertSame([['type' => 'vcs', 'url' => 'https://git.example.test/open.git']], $repos);
    }

    /** The same address twice, written by hand: one remove takes one of them. */
    public function test_an_exact_duplicate_loses_one_copy_not_both(): void
    {
        $this->write('{"repositories": [{"type": "vcs", "url": "https://git.example.test/a.git"}, {"type": "vcs", "url": "https://git.example.test/a.git"}]}');

        $this->repositories()->remove('https://git.example.test/a.git');

        $this->assertCount(1, json_decode((string) file_get_contents($this->dir.'/composer.json'), true)['repositories']);
    }

    public function test_an_address_that_is_not_there_is_still_refused(): void
    {
        $this->write($this->twoTokens());

        $this->expectException(RefusedException::class);

        $this->repositories()->remove('https://elsewhere.example.test/x.git');
    }

    // ── a raised pin is the same kind of edit ────────────────────────────────

    public function test_raising_a_pin_keeps_empty_objects_and_leaves_no_temporary_file(): void
    {
        $this->write('{"name": "a/site", "require": {"a/pinned": "1.0.0"}, "config": {}, "extra": {}}');

        $reflection = new ReflectionClass(ComposerSteps::class);
        $steps = $reflection->newInstanceWithoutConstructor();

        foreach (['installPath' => $this->dir, 'requested' => ['a/pinned'], 'mode' => 'update', 'repin' => ['a/pinned' => '1.1.0'], 'translator' => EnglishTranslator::make()] as $prop => $value) {
            $reflection->getProperty($prop)->setValue($steps, $value);
        }

        $reflection->getMethod('raisePins')->invoke($steps);

        $after = json_decode((string) file_get_contents($this->dir.'/composer.json'));

        $this->assertSame('1.1.0', $after->require->{'a/pinned'});
        $this->assertInstanceOf(\stdClass::class, $after->config);
        $this->assertInstanceOf(\stdClass::class, $after->extra);
        $this->assertSame([], glob($this->dir.'/composer.json.extension-manager-????????') ?: [], 'Written beside and renamed into place.');
        $this->assertComposerAccepts();
    }

    /** A composer.json it cannot read is refused, never replaced by one built from nothing. */
    public function test_raising_a_pin_in_a_broken_composer_json_is_refused(): void
    {
        $this->write('{"require": {"a/pinned": "1.0.0"');

        $reflection = new ReflectionClass(ComposerSteps::class);
        $steps = $reflection->newInstanceWithoutConstructor();

        foreach (['installPath' => $this->dir, 'requested' => ['a/pinned'], 'mode' => 'update', 'repin' => ['a/pinned' => '1.1.0'], 'translator' => EnglishTranslator::make()] as $prop => $value) {
            $reflection->getProperty($prop)->setValue($steps, $value);
        }

        try {
            $reflection->getMethod('raisePins')->invoke($steps);
            $this->fail('A broken composer.json was written over.');
        } catch (RefusedException) {
        }

        $this->assertSame('{"require": {"a/pinned": "1.0.0"', file_get_contents($this->dir.'/composer.json'));
    }

    // ── helpers ──────────────────────────────────────────────────────────────

    private function twoTokens(): string
    {
        return '{"repositories": [{"type": "vcs", "url": "https://alice:token-a@git.example.test/shared.git"}, {"type": "vcs", "url": "https://bob:token-b@git.example.test/shared.git"}]}';
    }

    private function write(string $raw): void
    {
        file_put_contents($this->dir.'/composer.json', $raw);
    }

    private function json(): JsonFile
    {
        return new JsonFile(EnglishTranslator::make(), $this->dir.'/composer.json');
    }

    private function repositories(): Repositories
    {
        return new Repositories(EnglishTranslator::make(), $this->json());
    }

    /** Composer's own schema, the judge that matters. */
    private function assertComposerAccepts(): void
    {
        $this->assertTrue((new ComposerJsonFile($this->dir.'/composer.json'))->validateSchema(ComposerJsonFile::LAX_SCHEMA));
    }
}
