<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\ExtensionManager\Tests\Unit;

use Flarum\ExtensionManager\Tests\unit\EnglishTranslator;
use Flarum\ExtensionManager\Work\Fetcher;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use ZipArchive;

/**
 * The unpacking half, which is where the security-relevant decisions are and
 * which needs no network to test.
 */
class FetcherTest extends TestCase
{
    private string $dir;

    protected function setUp(): void
    {
        $this->dir = sys_get_temp_dir().'/mw-fetch-'.bin2hex(random_bytes(6));
        mkdir($this->dir.'/staging', 0775, true);
    }

    protected function tearDown(): void
    {
        $this->rmrf($this->dir);
    }

    /** @param array<string,string> $entries */
    private function zip(string $name, array $entries): string
    {
        $path = $this->dir.'/'.$name;
        $zip = new ZipArchive();
        $zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        foreach ($entries as $entry => $content) {
            $zip->addFromString($entry, $content);
        }

        $zip->close();

        return $path;
    }

    /** @var list<array{request: \Psr\Http\Message\RequestInterface}> */
    private array $sent = [];

    /** A Fetcher whose HTTP client answers from $responses and records what it sent. */
    private function fetcher(array $responses, ?array $auth = null): Fetcher
    {
        $stack = HandlerStack::create(new MockHandler($responses));
        $this->sent = [];
        $stack->push(Middleware::history($this->sent));

        $authPath = null;

        if ($auth !== null) {
            $authPath = $this->dir.'/auth.json';
            file_put_contents($authPath, json_encode($auth));
        }

        return new Fetcher(EnglishTranslator::make(), new Client(['handler' => $stack]), $this->dir.'/staging', $authPath);
    }

    private function fetchLocal(string $archive, string $package, ?string $shasum = null): void
    {
        // Served by a mock client, so the download path is exercised without a network.
        $this->fetcher([new Response(200, [], (string) file_get_contents($archive))])->fetch($package, [
            'url' => 'https://example.test/archive.zip', 'type' => 'zip', 'reference' => null, 'shasum' => $shasum,
        ]);
    }

    private function source(string $url): array
    {
        return ['url' => $url, 'type' => 'zip', 'reference' => null, 'shasum' => null];
    }

    public function test_a_plain_http_archive_is_refused_before_any_request(): void
    {
        $fetcher = $this->fetcher([]);

        try {
            $fetcher->fetch('a/b', $this->source('http://example.test/a.zip'));
            $this->fail('an http:// archive should have been refused');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('HTTPS', $e->getMessage());
        }

        $this->assertSame([], $this->sent);
    }

    public function test_other_protocols_are_refused_too(): void
    {
        $this->expectException(RuntimeException::class);
        $this->fetcher([])->fetch('a/b', $this->source('file:///etc/passwd'));
    }

    public function test_a_redirect_that_leaves_https_is_refused(): void
    {
        $fetcher = $this->fetcher([new Response(302, ['Location' => 'http://example.test/a.zip'])]);

        try {
            $fetcher->fetch('a/b', $this->source('https://example.test/a.zip'));
            $this->fail('a redirect to http:// should have been refused');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('allowed redirect protocols', $e->getMessage());
        }

        $this->assertCount(1, $this->sent, 'the http:// address was never requested');
        $this->assertDirectoryDoesNotExist($this->dir.'/staging/a/b');
    }

    public function test_a_token_goes_to_its_exact_host_and_never_to_a_subdomain(): void
    {
        $archive = (string) file_get_contents($this->zip('h.zip', ['pkg/composer.json' => '{"name":"a/b"}']));
        $auth = ['bearer' => ['repo.example.test' => 'secret'], 'http-basic' => ['example.test' => ['username' => 'u', 'password' => 'p']]];

        $this->fetcher([new Response(200, [], $archive)], $auth)->fetch('a/b', $this->source('https://repo.example.test/a.zip'));
        $this->assertSame('Bearer secret', $this->sent[0]['request']->getHeaderLine('Authorization'));

        $this->fetcher([new Response(200, [], $archive)], $auth)->fetch('c/d', $this->source('https://cdn.repo.example.test/c.zip'));
        $this->assertFalse($this->sent[0]['request']->hasHeader('Authorization'), 'a subdomain is a different host');
    }

    public function test_a_github_token_is_sent_to_the_api_and_dropped_on_the_redirect_to_codeload(): void
    {
        $archive = (string) file_get_contents($this->zip('i.zip', ['pkg/composer.json' => '{"name":"a/b"}']));

        $this->fetcher([
            new Response(302, ['Location' => 'https://codeload.github.com/a/b/legacy.zip/abc']),
            new Response(200, [], $archive),
        ], ['github-oauth' => ['github.com' => 'ghp_secret']])->fetch('a/b', $this->source('https://api.github.com/repos/a/b/zipball/abc'));

        $this->assertSame('Bearer ghp_secret', $this->sent[0]['request']->getHeaderLine('Authorization'));
        $this->assertSame('codeload.github.com', $this->sent[1]['request']->getUri()->getHost());
        $this->assertFalse($this->sent[1]['request']->hasHeader('Authorization'), 'the token never leaves api.github.com');
        $this->assertFileExists($this->dir.'/staging/a/b/composer.json');
    }

    public function test_the_generated_wrapper_directory_is_stripped(): void
    {
        // GitHub wraps everything in "owner-repo-abc1234/". What lands in
        // staging has to be the package itself, or apply would move a directory
        // containing the package rather than the package.
        $archive = $this->zip('a.zip', [
            'vendor-pkg-9f8e7d6/composer.json' => '{"name":"a/b"}',
            'vendor-pkg-9f8e7d6/src/Thing.php' => '<?php // thing',
        ]);

        $this->fetchLocal($archive, 'a/b');

        $this->assertFileExists($this->dir.'/staging/a/b/composer.json');
        $this->assertFileExists($this->dir.'/staging/a/b/src/Thing.php');
        $this->assertDirectoryDoesNotExist($this->dir.'/staging/a/b/vendor-pkg-9f8e7d6');
    }

    public function test_an_archive_with_no_wrapper_is_left_alone(): void
    {
        $archive = $this->zip('b.zip', ['composer.json' => '{"name":"a/b"}', 'src/X.php' => '<?php']);

        $this->fetchLocal($archive, 'a/b');

        $this->assertFileExists($this->dir.'/staging/a/b/composer.json');
        $this->assertFileExists($this->dir.'/staging/a/b/src/X.php');
    }

    public function test_a_wrong_checksum_stops_it_before_anything_is_unpacked(): void
    {
        // The last point a substituted archive can be caught. After this,
        // apply moves it into place without asking further questions.
        $archive = $this->zip('c.zip', ['pkg/composer.json' => '{}']);

        try {
            $this->fetchLocal($archive, 'a/b', 'definitely-not-the-right-hash');
            $this->fail('a mismatched checksum should have stopped the fetch');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('checksum', $e->getMessage());
        }

        $this->assertDirectoryDoesNotExist($this->dir.'/staging/a/b', 'nothing should have been unpacked');
    }

    public function test_a_correct_checksum_passes(): void
    {
        $archive = $this->zip('d.zip', ['pkg/composer.json' => '{"name":"a/b"}']);

        $this->fetchLocal($archive, 'a/b', hash_file('sha1', $archive));

        $this->assertFileExists($this->dir.'/staging/a/b/composer.json');
    }

    public function test_an_archive_that_tries_to_escape_its_directory_is_refused(): void
    {
        // The oldest archive attack there is, and an archive is untrusted input
        // however trustworthy the index that named it.
        $archive = $this->zip('e.zip', [
            'pkg/composer.json' => '{}',
            'pkg/../../../evil.php' => '<?php // hello',
        ]);

        $this->expectException(RuntimeException::class);
        $this->fetchLocal($archive, 'a/b');
    }

    public function test_a_hostile_package_name_never_becomes_a_staging_path(): void
    {
        $archive = $this->zip('f.zip', ['pkg/composer.json' => '{}']);

        $this->expectException(RuntimeException::class);
        $this->fetchLocal($archive, 'a/../../etc');
    }

    public function test_fetching_an_already_staged_package_is_a_no_op(): void
    {
        // Required: the driver saves progress after the work, so a killed process
        // asks for the same package again on resume.
        $archive = $this->zip('g.zip', ['pkg/composer.json' => '{"name":"a/b"}']);

        $this->fetchLocal($archive, 'a/b');
        file_put_contents($this->dir.'/staging/a/b/marker', 'still here');

        $this->fetchLocal($archive, 'a/b');

        $this->assertFileExists($this->dir.'/staging/a/b/marker', 'it re-downloaded something already staged');
    }

    private function rmrf(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }
        $it = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($it as $i) {
            $i->isDir() ? @rmdir($i->getPathname()) : @unlink($i->getPathname());
        }
        @rmdir($dir);
    }
}
