<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\ExtensionManager\Tests\Unit;

use Flarum\ExtensionManager\Host\FlarumUpdater;
use Flarum\ExtensionManager\Host\UpdaterToken;
use Flarum\ExtensionManager\Tests\unit\EnglishTranslator;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

/**
 * A core update asks Flarum's updater to migrate with a one-time token, never
 * with the database credentials.
 */
class UpdaterTokenTest extends TestCase
{
    private string $dir;

    /** @var array<string,mixed> */
    private array $server;

    protected function setUp(): void
    {
        $this->dir = sys_get_temp_dir().'/mw-token-'.bin2hex(random_bytes(6));
        mkdir($this->dir.'/storage', 0775, true);
        file_put_contents($this->dir.'/config.php', "<?php return ['database' => ['database' => 'flarum', 'username' => 'forum', 'password' => 's3cret']];");
        $this->server = $_SERVER;
    }

    protected function tearDown(): void
    {
        $_SERVER = $this->server;
        $_POST = [];
        exec('rm -rf '.escapeshellarg($this->dir));
    }

    public function test_a_token_works_once(): void
    {
        $token = UpdaterToken::issue($this->dir.'/storage');

        $this->assertFalse(UpdaterToken::redeem($this->dir.'/storage', 'not-it'));
        $this->assertTrue(UpdaterToken::redeem($this->dir.'/storage', $token));
        $this->assertFalse(UpdaterToken::redeem($this->dir.'/storage', $token), 'single use');
    }

    public function test_only_the_hash_is_kept_and_only_its_owner_can_read_it(): void
    {
        $token = UpdaterToken::issue($this->dir.'/storage');
        $file = $this->dir.'/storage/extension-manager/updater-token';

        $this->assertStringNotContainsString($token, (string) file_get_contents($file));
        $this->assertSame(0600, fileperms($file) & 0777);
    }

    public function test_an_expired_token_is_refused(): void
    {
        $token = UpdaterToken::issue($this->dir.'/storage');
        $file = $this->dir.'/storage/extension-manager/updater-token';
        $data = json_decode((string) file_get_contents($file), true);
        file_put_contents($file, json_encode(['expires' => time() - 1] + $data));

        $this->assertFalse(UpdaterToken::redeem($this->dir.'/storage', $token));
    }

    public function test_the_request_carrying_it_gets_the_credentials_from_config_php(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER[UpdaterToken::SERVER_KEY] = UpdaterToken::issue($this->dir.'/storage');

        UpdaterToken::honour($this->dir, $this->dir.'/storage');

        $this->assertSame(['databaseUsername' => 'forum', 'databasePassword' => 's3cret'], $_POST);
    }

    public function test_a_wrong_token_gets_nothing(): void
    {
        UpdaterToken::issue($this->dir.'/storage');
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER[UpdaterToken::SERVER_KEY] = str_repeat('0', 64);
        $_POST = ['untouched' => '1'];

        UpdaterToken::honour($this->dir, $this->dir.'/storage');

        $this->assertSame(['untouched' => '1'], $_POST);
    }

    public function test_the_header_name_is_the_one_the_bootstrap_checks(): void
    {
        $this->assertSame('HTTP_'.strtoupper(str_replace('-', '_', UpdaterToken::HEADER)), UpdaterToken::SERVER_KEY);
        $this->assertStringContainsString(
            "\$_SERVER['".UpdaterToken::SERVER_KEY."']",
            (string) file_get_contents(dirname(__DIR__, 2).'/bootstrap/web-reset.php')
        );
    }

    public function test_the_updater_is_sent_a_token_and_never_the_credentials(): void
    {
        $sent = [];
        $stack = HandlerStack::create(new MockHandler([new Response(200, [], 'Migrated')]));
        $stack->push(Middleware::history($sent));

        $updater = new FlarumUpdater(
            EnglishTranslator::make(),
            new Client(['handler' => $stack]),
            'https://forum.example.test/',
            ['username' => 'forum', 'password' => 's3cret'],
            $this->dir.'/storage'
        );

        $this->assertSame('Migrated', $updater->migrate());
        $this->assertCount(1, $sent);
        $request = $sent[0]['request'];
        $this->assertSame('127.0.0.1', $request->getUri()->getHost(), 'the web server on this machine first');
        $this->assertSame('forum.example.test', $request->getHeaderLine('Host'));
        $this->assertSame(64, strlen($request->getHeaderLine(UpdaterToken::HEADER)));
        $this->assertStringNotContainsString('s3cret', (string) $request->getBody());
        $this->assertFileDoesNotExist($this->dir.'/storage/extension-manager/updater-token', 'never left lying about');
    }

    public function test_the_public_address_is_only_ever_sent_a_token(): void
    {
        $sent = [];
        $stack = HandlerStack::create(new MockHandler([
            new Response(301, ['Location' => 'https://forum.example.test/']),
            new Response(500, [], 'Incorrect database credentials.'),
            new Response(200, [], 'Migrated'),
        ]));
        $stack->push(Middleware::history($sent));

        $updater = new FlarumUpdater(
            EnglishTranslator::make(),
            new Client(['handler' => $stack]),
            'https://forum.example.test/',
            ['username' => 'forum', 'password' => 's3cret'],
            $this->dir.'/storage'
        );

        $this->assertSame('Migrated', $updater->migrate());
        $this->assertCount(3, $sent);
        $this->assertSame('forum.example.test', $sent[1]['request']->getUri()->getHost());
        $this->assertStringNotContainsString('s3cret', (string) $sent[1]['request']->getBody());

        // A token the site could not redeem: the credentials go to this machine only.
        $this->assertSame('127.0.0.1', $sent[2]['request']->getUri()->getHost());
        $this->assertStringContainsString('s3cret', (string) $sent[2]['request']->getBody());
    }
}
