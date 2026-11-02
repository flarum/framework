<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Mcp\Tests\integration\protocol;

use Flarum\Extend\ExtenderInterface;
use Flarum\Extension\Extension;
use Flarum\Foundation\Paths;
use Flarum\Locale\LocaleManager;
use Flarum\Mcp\Tests\integration\McpTestCase;
use Flarum\User\User;
use Illuminate\Contracts\Container\Container;
use PHPUnit\Framework\Attributes\Test;

/**
 * /mcp stays out of the forum's browser session and speaks the bearer's language.
 */
class SessionAndLocaleTest extends McpTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->extend(new class implements ExtenderInterface {
            public function extend(Container $container, ?Extension $extension = null): void
            {
                $container->extend(LocaleManager::class, function (LocaleManager $locales) {
                    $locales->addLocale('de', 'Deutsch');

                    return $locales;
                });
            }
        });

        $this->prepareDatabase([
            User::class => [
                $this->normalUser(),
                ['id' => 3, 'username' => 'german', 'email' => 'german@machine.local', 'is_email_confirmed' => 1, 'preferences' => json_encode(['locale' => 'de'])],
            ],
        ]);
    }

    #[Test]
    public function mcp_responses_start_no_session(): void
    {
        $sessions = $this->sessionFiles();

        // With a browser's cookies too: they are neither read nor refreshed.
        $loggedIn = $this->request('GET', '/', ['authenticatedAs' => 2]);
        $response = $this->send(
            $this->mcpRequest(['jsonrpc' => '2.0', 'id' => 1, 'method' => 'tools/list', 'params' => [
                '_meta' => [
                    'io.modelcontextprotocol/protocolVersion' => self::MODERN,
                    'io.modelcontextprotocol/clientCapabilities' => new \stdClass(),
                ],
            ]], [...$this->bearer($this->developerToken(2)), 'MCP-Protocol-Version' => self::MODERN, 'Mcp-Method' => 'tools/list'])
                ->withCookieParams($loggedIn->getCookieParams())
        );

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame([], $response->getHeader('Set-Cookie'));
        $this->assertFalse($response->hasHeader('X-CSRF-Token'));
        $this->assertSame($sessions, $this->sessionFiles());

        // Other forum routes still get theirs.
        $this->assertNotSame([], $this->send($this->request('GET', '/.well-known/oauth-protected-resource'))->getHeader('Set-Cookie'));
    }

    #[Test]
    public function the_locale_follows_the_bearer(): void
    {
        $this->assertSame('de', $this->overviewLocale(3));
        $this->assertSame('en', $this->overviewLocale(2));
    }

    protected function overviewLocale(int $userId): string
    {
        $read = $this->rpcResult($this->modern('resources/read', ['uri' => 'flarum://forum'], $this->bearer($this->developerToken($userId)), 'flarum://forum'));

        return json_decode($read['contents'][0]['text'], true)['forum']['locale'];
    }

    /**
     * @return list<string>
     */
    protected function sessionFiles(): array
    {
        $directory = $this->app()->getContainer()->make(Paths::class)->storage.'/sessions';

        return is_dir($directory) ? array_values(array_diff((array) scandir($directory), ['.', '..', '.gitignore'])) : [];
    }
}
