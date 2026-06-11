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
use Psr\Http\Message\ResponseInterface;

class TestCase extends \Flarum\Testing\integration\TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        AuditLogger::$testMode = true;

        $this->extension('flarum-audit');

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
        // Get a CSRF token
        $csrfResponse = $this->send($this->request('GET', '/'));

        $response = $this->send($this->request($method, $path, $options + [
            'cookiesFrom' => $csrfResponse,
        ])->withAddedHeader('X-CSRF-Token', $csrfResponse->getHeaderLine('X-CSRF-Token')));

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
