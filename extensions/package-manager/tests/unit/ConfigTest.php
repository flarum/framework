<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\ExtensionManager\Tests\Unit;

use Flarum\ExtensionManager\Config\AuthTokens;
use Flarum\ExtensionManager\Config\JsonFile;
use Flarum\ExtensionManager\Config\Repositories;
use Flarum\ExtensionManager\Config\Stability;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * 🚨 The rules these protect:
 *
 *   - a stored credential is never readable back out of this extension;
 *   - a malformed composer.json is never written, and never overwritten;
 *   - the settings that decide whether Composer can find anything are the ones
 *     that made the tool it replaces impossible to remove.
 */
class ConfigTest extends TestCase
{
    private string $dir;

    protected function setUp(): void
    {
        $this->dir = sys_get_temp_dir().'/mw-config-'.bin2hex(random_bytes(4));
        mkdir($this->dir, 0775, true);
    }

    protected function tearDown(): void
    {
        exec('rm -rf '.escapeshellarg($this->dir));
    }

    private function file(string $name, ?array $data = null): JsonFile
    {
        $path = $this->dir.'/'.$name;

        if ($data !== null) {
            file_put_contents($path, json_encode($data));
        }

        return new JsonFile($path);
    }

    // ── credentials ─────────────────────────────────────────────────────────

    public function test_a_stored_credential_is_never_returned(): void
    {
        /*
         * 🚨 The single most important test here. A settings screen that shows
         * your GitHub token back to you turns every screenshot and support
         * ticket into a credential leak, and the person it leaks from has no
         * reason to think anything happened.
         */
        $auth = new AuthTokens($this->file('auth.json', []));
        $auth->set('github-oauth', 'github.com', 'ghp_thismustneverappear');
        $auth->set('http-basic', 'floxum.com', 'sekrit', 'ernest');

        $reported = json_encode($auth->all());

        $this->assertStringNotContainsString('ghp_thismustneverappear', $reported);
        $this->assertStringNotContainsString('sekrit', $reported);

        // It does say WHICH hosts have one, which is the useful half.
        $this->assertStringContainsString('github.com', $reported);
        $this->assertStringContainsString('floxum.com', $reported);
        // And a username is not a secret — it is how two accounts are told apart.
        $this->assertStringContainsString('ernest', $reported);
    }

    public function test_the_credential_really_is_written_for_composer(): void
    {
        // Write-only to the admin, but Composer still has to be able to read it.
        $auth = new AuthTokens($this->file('auth.json', []));
        $auth->set('github-oauth', 'github.com', 'ghp_token');

        $onDisk = json_decode(file_get_contents($this->dir.'/auth.json'), true);

        $this->assertSame('ghp_token', $onDisk['github-oauth']['github.com']);
        $this->assertSame('0600', substr(sprintf('%o', fileperms($this->dir.'/auth.json')), -4));
    }

    public function test_http_basic_needs_a_username(): void
    {
        $this->expectExceptionMessageMatches('/username/');
        (new AuthTokens($this->file('auth.json', [])))->set('http-basic', 'floxum.com', 'pw');
    }

    public function test_a_url_pasted_where_a_hostname_belongs_is_refused(): void
    {
        // Composer matches on hostname; a URL here silently never matches.
        $this->expectExceptionMessageMatches('/hostname/');
        (new AuthTokens($this->file('auth.json', [])))->set('github-oauth', 'https://github.com/', 'x');
    }

    public function test_removing_a_credential_removes_the_empty_kind_too(): void
    {
        $auth = new AuthTokens($this->file('auth.json', []));
        $auth->set('bearer', 'floxum.com', 'tok');
        $auth->remove('bearer', 'floxum.com');

        $this->assertSame([], json_decode(file_get_contents($this->dir.'/auth.json'), true));
    }

    // ── repositories ────────────────────────────────────────────────────────

    public function test_repositories_are_listed_with_their_type(): void
    {
        $repos = new Repositories($this->file('composer.json', ['repositories' => [
            ['type' => 'vcs', 'url' => 'https://github.com/a/b'],
            ['type' => 'composer', 'url' => 'https://floxum.com/composer'],
        ]]));

        $this->assertSame(['vcs', 'composer'], array_column($repos->all(), 'type'));
    }

    public function test_a_duplicate_repository_is_refused(): void
    {
        $repos = new Repositories($this->file('composer.json', ['repositories' => [
            ['type' => 'vcs', 'url' => 'https://github.com/a/b'],
        ]]));

        $this->expectExceptionMessageMatches('/already configured/');
        $repos->add('vcs', 'https://github.com/a/b/');
    }

    public function test_a_new_repository_goes_last(): void
    {
        // Composer resolves in order, so appending cannot change how anything
        // already installed is found.
        $repos = new Repositories($this->file('composer.json', ['repositories' => [
            ['type' => 'composer', 'url' => 'https://floxum.com/composer'],
        ]]));

        $repos->add('vcs', 'https://github.com/new/thing');

        $this->assertSame('https://floxum.com/composer', $repos->all()[0]['url']);
        $this->assertSame('https://github.com/new/thing', $repos->all()[1]['url']);
    }

    public function test_nonsense_is_not_written_into_composer_json(): void
    {
        $repos = new Repositories($this->file('composer.json', []));

        try {
            $repos->add('vcs', 'not a url');
            $this->fail('should have been refused');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('repository URL', $e->getMessage());
        }

        $this->expectExceptionMessageMatches('/vcs, composer or path/');
        $repos->add('magic', 'https://example.test/x');
    }

    public function test_removing_a_repository_that_is_not_there_says_so(): void
    {
        $repos = new Repositories($this->file('composer.json', ['repositories' => []]));

        $this->expectExceptionMessageMatches('/not in composer.json/');
        $repos->remove('https://github.com/a/b');
    }

    // ── stability ───────────────────────────────────────────────────────────

    public function test_stability_reports_and_sets(): void
    {
        $s = new Stability($this->file('composer.json', ['minimum-stability' => 'beta', 'prefer-stable' => true]));

        $this->assertSame('beta', $s->current()['minimumStability']);
        $this->assertTrue($s->current()['preferStable']);

        $s->set('RC', false);
        $this->assertSame('RC', $s->current()['minimumStability']);
        $this->assertFalse($s->current()['preferStable']);
    }

    public function test_an_invented_stability_level_is_refused(): void
    {
        $this->expectExceptionMessageMatches('/must be one of/');
        (new Stability($this->file('composer.json', [])))->set('mostly-fine', true);
    }

    public function test_every_level_explains_what_it_means(): void
    {
        // The consequence is what somebody is deciding about, not the word.
        $locale = \Symfony\Component\Yaml\Yaml::parseFile(__DIR__.'/../../locale/en.yml')['flarum-extension-manager']['admin'];

        foreach (Stability::LEVELS as $level) {
            $key = 'level_'.strtolower($level).'_means';
            $this->assertNotEmpty($locale[$key] ?? null, "$level has no explanation");
        }

        // 🚨 Flarum 2 is stable now; the old copy said it was still a release candidate.
        $this->assertStringContainsString('Flarum 2 itself is stable', $locale['level_stable_means']);
    }

    // ── the file itself ─────────────────────────────────────────────────────

    public function test_a_broken_composer_json_is_refused_rather_than_replaced(): void
    {
        /*
         * 🚨 Treating unreadable JSON as an empty array would replace a file
         * somebody could still fix by hand with one built from nothing.
         */
        file_put_contents($this->dir.'/composer.json', '{"require": {,,,}');

        $this->expectExceptionMessageMatches('/not valid JSON/');
        (new Repositories(new JsonFile($this->dir.'/composer.json')))->all();
    }

    public function test_the_previous_contents_are_kept(): void
    {
        $s = new Stability($this->file('composer.json', ['minimum-stability' => 'stable', 'name' => 'flarum/flarum']));
        $s->set('beta', true);

        $backups = glob($this->dir.'/composer.json.extension-manager-backup-*');
        $this->assertNotEmpty($backups, 'a write with no backup is a write that cannot be undone');
        $this->assertStringContainsString('stable', file_get_contents($backups[0]));
    }

    public function test_writing_preserves_everything_else_in_the_file(): void
    {
        // The file holds the site's entire dependency list. Rewriting it must
        // change one key and leave the rest byte-identical in meaning.
        $original = [
            'name' => 'flarum/flarum',
            'require' => ['flarum/core' => '^2.0', 'ernestdefoe/bespoke' => 'dev-main'],
            'repositories' => [['type' => 'vcs', 'url' => 'https://github.com/a/b']],
            'minimum-stability' => 'stable',
        ];

        $s = new Stability($this->file('composer.json', $original));
        $s->set('beta', true);

        $after = json_decode(file_get_contents($this->dir.'/composer.json'), true);

        $this->assertSame($original['require'], $after['require']);
        $this->assertSame($original['repositories'], $after['repositories']);
        $this->assertSame('flarum/flarum', $after['name']);
        $this->assertSame('beta', $after['minimum-stability']);
    }
}
