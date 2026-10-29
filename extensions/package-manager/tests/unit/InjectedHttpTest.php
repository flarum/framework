<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\ExtensionManager\Tests\Unit;

use Flarum\ExtensionManager\Discover\Cache;
use Flarum\ExtensionManager\Discover\Packagist;
use Flarum\ExtensionManager\Plan\Nightly;
use Flarum\ExtensionManager\Tests\unit\EnglishTranslator;
use Flarum\ExtensionManager\Work\UpdateCheck;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

/**
 * Packagist is asked through the Guzzle client the extension is given, and
 * an answer that is an error, or no answer at all, reads as "could not
 * check", never as an empty list.
 */
class InjectedHttpTest extends TestCase
{
    private string $dir;

    /** @var list<array{request: \Psr\Http\Message\RequestInterface}> */
    private array $sent = [];

    protected function setUp(): void
    {
        $this->dir = sys_get_temp_dir().'/mw-http-'.bin2hex(random_bytes(4));
        mkdir($this->dir, 0775, true);
    }

    protected function tearDown(): void
    {
        exec('rm -rf '.escapeshellarg($this->dir));
    }

    private function client(mixed ...$answers): Client
    {
        $stack = HandlerStack::create(new MockHandler($answers));
        $stack->push(Middleware::history($this->sent));

        return new Client(['handler' => $stack]);
    }

    private function p2(string $name, string ...$versions): string
    {
        return (string) json_encode(['packages' => [$name => array_map(fn ($v) => ['version' => $v], $versions)]]);
    }

    public function test_the_update_check_asks_packagist_through_the_client(): void
    {
        $check = new UpdateCheck($this->dir.'/updates.json', $this->client(new Response(200, [], $this->p2('acme/widget', '1.1.0', '1.0.0'))));

        $this->assertSame(['1.1.0', '1.0.0'], $check->fromPackagist('acme/widget'));
        $this->assertSame('https://repo.packagist.org/p2/acme/widget.json', (string) $this->sent[0]['request']->getUri());
        $this->assertSame('the Extension Manager', $this->sent[0]['request']->getHeaderLine('User-Agent'));
    }

    public function test_the_update_check_reads_an_error_or_no_answer_as_unknown(): void
    {
        $request = new Request('GET', 'https://repo.packagist.org/');
        $check = new UpdateCheck($this->dir.'/updates.json', $this->client(new Response(404), new ConnectException('down', $request)));

        $this->assertNull($check->fromPackagist('acme/widget'));
        $this->assertNull($check->fromPackagist('acme/widget'));
    }

    public function test_the_nightly_branch_is_asked_of_packagist_through_the_client(): void
    {
        $nightly = new Nightly($this->dir.'/composer.json', $this->dir.'/composer.lock', $this->client(
            new Response(200, [], $this->p2('flarum/core', '2.x-dev')),
            new Response(500),
        ));

        $this->assertSame(['2.x-dev'], $nightly->devVersions('flarum/core'));
        $this->assertSame('https://repo.packagist.org/p2/flarum/core~dev.json', (string) $this->sent[0]['request']->getUri());
        $this->assertNull($nightly->devVersions('flarum/core'), 'an error is unknown, not "no branches"');
    }

    public function test_discover_reports_an_error_answer_as_unreachable(): void
    {
        $packagist = new Packagist(EnglishTranslator::make(), new Cache($this->dir), $this->client(new Response(503, [], '{"results":[]}')));

        $found = $packagist->search('widget');

        $this->assertSame([], $found['results']);
        $this->assertArrayHasKey('error', $found);
        $this->assertStringStartsWith('https://packagist.org/search.json', (string) $this->sent[0]['request']->getUri());
    }
}
