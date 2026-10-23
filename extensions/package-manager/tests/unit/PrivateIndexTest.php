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
use Flarum\ExtensionManager\Tests\unit\EnglishTranslator;
use Flarum\ExtensionManager\Work\PrivateIndex;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * Checking the extensions Packagist has never heard of.
 *
 * These are the paid ones. Before this, they came back "uncheckable" on every
 * run and their owner was never told a newer version existed.
 */
class PrivateIndexTest extends TestCase
{
    private string $dir;

    private MockHandler $responses;

    protected function setUp(): void
    {
        $this->dir = sys_get_temp_dir().'/mw-private-'.bin2hex(random_bytes(6));
        mkdir($this->dir, 0775, true);
        $this->responses = new MockHandler();
    }

    protected function tearDown(): void
    {
        foreach (glob($this->dir.'/*') ?: [] as $f) {
            @unlink($f);
        }
        @rmdir($this->dir);
    }

    private function index(array $composer, array $auth = []): PrivateIndex
    {
        file_put_contents($this->dir.'/composer.json', json_encode($composer));
        file_put_contents($this->dir.'/auth.json', json_encode($auth));

        return new PrivateIndex(
            new Repositories(EnglishTranslator::make(), new JsonFile(EnglishTranslator::make(), $this->dir.'/composer.json')),
            new AuthTokens(EnglishTranslator::make(), new JsonFile(EnglishTranslator::make(), $this->dir.'/auth.json')),
            new Client(['handler' => HandlerStack::create($this->responses)])
        );
    }

    /** Only `composer` repositories have an index to read. */
    public function test_it_only_asks_composer_repositories(): void
    {
        $index = $this->index(['repositories' => [
            ['type' => 'composer', 'url' => 'https://example.test/composer'],
            ['type' => 'vcs', 'url' => 'https://github.com/someone/thing'],
            ['type' => 'path', 'url' => './packages/local'],
        ]]);

        $method = (new ReflectionClass($index))->getMethod('composerRepositories');

        $this->assertSame(['https://example.test/composer'], $method->invoke($index));
    }

    /**
     * A vcs repository would have to be cloned to answer, which is not
     * something to do on a nightly schedule on somebody's shared host.
     */
    public function test_a_repository_with_no_url_or_a_local_path_is_ignored(): void
    {
        $index = $this->index(['repositories' => [
            ['type' => 'composer', 'url' => ''],
            ['type' => 'composer', 'url' => '/srv/packages'],
        ]]);

        $method = (new ReflectionClass($index))->getMethod('composerRepositories');

        $this->assertSame([], $method->invoke($index));
    }

    public function test_the_same_repository_listed_twice_is_asked_once(): void
    {
        $index = $this->index(['repositories' => [
            ['type' => 'composer', 'url' => 'https://example.test/composer'],
            ['type' => 'composer', 'url' => 'https://example.test/composer/'],
        ]]);

        $method = (new ReflectionClass($index))->getMethod('composerRepositories');

        $this->assertCount(1, $method->invoke($index));
    }

    /** A Composer index keys by version and repeats it inside; both shapes work. */
    public function test_it_reads_versions_out_of_a_composer_index(): void
    {
        $index = $this->index([]);

        $method = (new ReflectionClass($index))->getMethod('fetchIndex');
        $parsed = $this->parse($index, [
            'packages' => [
                'vendor/paid' => ['1.0.0' => ['version' => '1.0.0'], '1.1.0' => ['version' => '1.1.0']],
                'vendor/sparse' => ['2.0.0' => []],
                'vendor/empty' => [],
                'vendor/broken' => 'not an array',
            ],
        ]);

        $this->assertSame(['1.0.0', '1.1.0'], $parsed['vendor/paid']);
        $this->assertSame(['2.0.0'], $parsed['vendor/sparse']);
        $this->assertArrayNotHasKey('vendor/empty', $parsed);
        $this->assertArrayNotHasKey('vendor/broken', $parsed);
    }

    /**
     * Unknown must stay null, never an empty list.
     *
     * `UpdateCheck` treats null as "could not check" and reports it honestly.
     * An empty array would read as "checked, nothing newer" — a guess presented
     * as a fact, about the one extension somebody paid for.
     */
    public function test_a_package_no_repository_knows_is_unknown_not_up_to_date(): void
    {
        $index = $this->index(['repositories' => []]);

        $this->assertNull($index->versionsFor('vendor/paid'));
    }

    /**
     * The credential never leaves as a credential.
     *
     * `all()` is what the admin API calls, and it must keep reporting hosts and
     * kinds and nothing else however this file grows. `authorizationFor()`
     * returns a finished header value so no caller ever holds the secret.
     */
    public function test_stored_secrets_are_never_reported(): void
    {
        file_put_contents($this->dir.'/auth.json', json_encode([
            'http-basic' => ['example.test' => ['username' => 'token', 'password' => 'sup3r-secret']],
            'bearer' => ['other.test' => 'another-secret'],
        ]));

        $auth = new AuthTokens(EnglishTranslator::make(), new JsonFile(EnglishTranslator::make(), $this->dir.'/auth.json'));

        $reported = json_encode($auth->all());

        $this->assertStringNotContainsString('sup3r-secret', (string) $reported);
        $this->assertStringNotContainsString('another-secret', (string) $reported);
        $this->assertStringContainsString('example.test', (string) $reported);
    }

    public function test_http_basic_becomes_a_basic_header(): void
    {
        file_put_contents($this->dir.'/auth.json', json_encode([
            'http-basic' => ['example.test' => ['username' => 'token', 'password' => 'shh']],
        ]));

        $auth = new AuthTokens(EnglishTranslator::make(), new JsonFile(EnglishTranslator::make(), $this->dir.'/auth.json'));

        $this->assertSame('Basic '.base64_encode('token:shh'), $auth->authorizationFor('https://example.test/composer/packages.json'));
    }

    public function test_a_bearer_token_becomes_a_bearer_header(): void
    {
        file_put_contents($this->dir.'/auth.json', json_encode([
            'bearer' => ['example.test' => 'shh'],
        ]));

        $auth = new AuthTokens(EnglishTranslator::make(), new JsonFile(EnglishTranslator::make(), $this->dir.'/auth.json'));

        $this->assertSame('Bearer shh', $auth->authorizationFor('https://example.test/packages.json'));
    }

    /**
     * Composer matches a credential by the URL's exact host: never a
     * subdomain, never another host, never over plain HTTP. GitHub's API is
     * the one alias, as in Composer.
     */
    public function test_a_credential_is_sent_only_to_its_exact_host(): void
    {
        file_put_contents($this->dir.'/auth.json', json_encode([
            'http-basic' => ['example.test' => ['username' => 'token', 'password' => 'shh']],
            'github-oauth' => ['github.com' => 'ghp'],
        ]));

        $auth = new AuthTokens(EnglishTranslator::make(), new JsonFile(EnglishTranslator::make(), $this->dir.'/auth.json'));

        $this->assertNotNull($auth->authorizationFor('https://EXAMPLE.TEST/composer'));
        $this->assertNull($auth->authorizationFor('https://cdn.example.test/composer'), 'not a subdomain');
        $this->assertNull($auth->authorizationFor('https://example.test:8443/composer'), 'not another port');
        $this->assertNull($auth->authorizationFor('http://example.test/composer'), 'not over plain HTTP');
        $this->assertNull($auth->authorizationFor('https://other.test/composer'));
        $this->assertSame('Bearer ghp', $auth->authorizationFor('https://api.github.com/repos/a/b/tags'));
        $this->assertNull($auth->authorizationFor('https://raw.githubusercontent.com/a/b/HEAD/composer.json'));
        $this->assertNull($auth->authorizationFor('https://codeload.github.com/a/b/zip/abc'));
    }

    /** A repository with no credential is a normal state, not an error. */
    public function test_no_credential_is_not_an_error(): void
    {
        $auth = new AuthTokens(EnglishTranslator::make(), new JsonFile(EnglishTranslator::make(), $this->dir.'/auth.json'));

        $this->assertNull($auth->authorizationFor('https://example.test/packages.json'));
    }

    public function test_it_recognises_github_vcs_repositories(): void
    {
        $index = $this->index(['repositories' => [
            ['type' => 'vcs', 'url' => 'https://github.com/ernestdefoe/fantasy-flarum'],
            ['type' => 'vcs', 'url' => 'https://github.com/ernestdefoe/bespoke.git'],
            ['type' => 'vcs', 'url' => 'git@github.com:ernestdefoe/picks.git'],
            ['type' => 'vcs', 'url' => 'https://gitlab.com/someone/thing'],
        ]]);

        $method = (new ReflectionClass($index))->getMethod('githubRepositories');
        $found = $method->invoke($index);

        $this->assertSame(
            ['ernestdefoe/fantasy-flarum', 'ernestdefoe/bespoke', 'ernestdefoe/picks'],
            array_keys($found)
        );
    }

    /**
     * The repository name is NOT the package name.
     *
     * github.com/ernestdefoe/fantasy-flarum publishes `ernestdefoe/fantasy`,
     * and gameday-flarum publishes `ernestdefoe/gameday`. Guessing the package
     * from the URL would check the wrong thing — or check nothing while
     * appearing to work, which is how the whole feature was useless before.
     * The package name is read from the repository's own composer.json.
     */
    public function test_the_package_name_is_read_not_guessed_from_the_url(): void
    {
        $index = $this->index([]);
        $reflection = new ReflectionClass($index);

        // Pre-seed the lookup the way a fetch would, then assert it is used.
        $names = $reflection->getProperty('packageNames');
        $names->setValue($index, ['https://github.com/ernestdefoe/fantasy-flarum' => 'ernestdefoe/fantasy']);

        $method = $reflection->getMethod('packageOf');

        $this->assertSame(
            'ernestdefoe/fantasy',
            $method->invoke($index, 'https://github.com/ernestdefoe/fantasy-flarum'),
            'The package must come from the repository, not from its URL.'
        );
    }

    /** A repository that cannot be identified must not poison the results. */
    public function test_an_unidentifiable_repository_is_simply_skipped(): void
    {
        $index = $this->index([]);
        $method = (new ReflectionClass($index))->getMethod('packageOf');

        $this->assertNull($method->invoke($index, 'https://example.test/not-github'));
    }

    /** A GitHub vcs repository's tags each get their release page, by tag name. */
    public function test_github_tags_carry_their_release_links(): void
    {
        $index = $this->index(['repositories' => [['type' => 'vcs', 'url' => 'https://github.com/ernestdefoe/fantasy-flarum']]]);
        $reflection = new ReflectionClass($index);
        $reflection->getProperty('packageNames')->setValue($index, ['https://github.com/ernestdefoe/fantasy-flarum' => 'ernestdefoe/fantasy']);
        $reflection->getProperty('tagLists')->setValue($index, ['ernestdefoe/fantasy-flarum' => ['v1.1.0', 'v1.0.0']]);

        $this->assertSame(['v1.1.0', 'v1.0.0'], $index->versionsFor('ernestdefoe/fantasy'));
        $this->assertSame([
            'v1.1.0' => 'https://github.com/ernestdefoe/fantasy-flarum/releases/tag/v1.1.0',
            'v1.0.0' => 'https://github.com/ernestdefoe/fantasy-flarum/releases/tag/v1.0.0',
        ], $index->releaseNotesFor('ernestdefoe/fantasy'));
        $this->assertSame([], $index->releaseNotesFor('ernestdefoe/unknown'));
    }

    /** A Composer index gives a release link only where it names a GitHub source. */
    public function test_a_composer_index_links_releases_only_from_a_github_source(): void
    {
        $index = $this->index([]);
        $this->parse($index, ['packages' => ['vendor/paid' => [
            '1.1.0' => ['version' => '1.1.0', 'source' => ['url' => 'https://github.com/vendor/paid.git']],
            '1.0.0' => ['version' => '1.0.0', 'dist' => ['url' => 'https://vendor.example/paid-1.0.0.zip']],
        ]]]);

        $this->assertSame(['1.1.0' => 'https://github.com/vendor/paid/releases/tag/1.1.0'], $index->releaseNotesFor('vendor/paid'));
    }

    /** @return array<string, list<string>> */
    private function parse(PrivateIndex $index, array $payload): array
    {
        // Served by the mock client, so no network is involved.
        $this->responses->append(new Response(200, [], (string) json_encode($payload)));

        $reflection = new ReflectionClass($index);
        $reflection->getProperty('indexes')->setValue($index, []);

        return $reflection->getMethod('fetchIndex')->invoke($index, 'https://repo.example.test') ?? [];
    }
}
