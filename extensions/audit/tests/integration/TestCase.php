<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Audit\Tests\integration;

use Flarum\Audit\AuditLog;
use Flarum\Audit\AuditLogger;
use Flarum\Extend\Csrf;
use Psr\Http\Message\ResponseInterface;

class TestCase extends \Flarum\Testing\integration\TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        AuditLogger::$testMode = true;

        $this->extension('flarum-audit');

        // Exempt the routes our tests POST to as a guest from CSRF. This is the standard way to
        // exercise these flows in the test harness, rather than the unreliable session/token dance.
        $this->extend(
            (new Csrf())
                ->exemptRoute('register')
                ->exemptRoute('login')
                ->exemptRoute('logout')
                ->exemptRoute('confirmEmail.submit')
                ->exemptRoute('savePassword')
                ->exemptRoute('forgot')
        );

        $this->prepareDatabase([
            // Make sure the audit log is cleared before each test
            'audit_log' => [],
        ]);
    }

    protected function sendSuccessfulRequest(string $method, string $path, array $options = [], int $statusCode = 200, ?int $authenticatedAs = 1): ResponseInterface
    {
        $response = $this->send($this->request($method, $path, $options + [
            'authenticatedAs' => $authenticatedAs,
        ]));

        // Helps troubleshoot 422 errors during development since they are logged nowhere and it's a pain to guess what's wrong
        if ($response->getStatusCode() === 422) {
            echo $response->getBody()->getContents();

            $response->getBody()->rewind();
        }

        $this->assertEquals($statusCode, $response->getStatusCode(), 'Assert request status code');

        return $response;
    }

    protected function sendForumCsrfRequest(string $method, string $path, array $options = [], int $statusCode = 200): ResponseInterface
    {
        // The relevant routes are exempted from CSRF in setUp(), so a plain guest request works
        // without the unreliable GET-token-then-POST dance.
        $response = $this->send($this->request($method, $path, $options));

        $this->assertEquals($statusCode, $response->getStatusCode(), 'Assert request status code');

        return $response;
    }

    protected function assertLogExists(string $action, array $payload = null, ?int $actorId = 1, ?int $skip = 0): void
    {
        /**
         * @var AuditLog $log
         */
        $log = AuditLog::query()->where('action', $action)->skip($skip)->first();

        $this->assertNotNull($log, 'Asserting log exists');

        $this->assertEquals($actorId, $log->actor_id, 'Asserting logged actor');

        $this->assertEquals($payload, $log->payload, 'Asserting logged payload');

        $this->assertEquals('127.0.0.1', $log->ip_address, 'Asserting logged IP');
    }

    protected function assertLogDoesntExist(string $action): void
    {
        $log = AuditLog::query()->where('action', $action)->first();

        $this->assertNull($log, 'Asserting log doesn\'t exist');
    }
}
