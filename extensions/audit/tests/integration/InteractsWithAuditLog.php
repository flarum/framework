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

/**
 * Shared audit-log test helpers.
 *
 * First-party extensions own their audit integration (registered via the
 * Flarum\Audit\Extend\Audit extender behind an Extend\Conditional). Their integration tests
 * therefore live in that extension's own suite, but still need to enable flarum-audit and
 * assert against the resulting log entries. This trait provides that shared surface so each
 * extension doesn't reimplement the same assertions.
 *
 * Usage from an extension's integration test:
 *
 *     class AuditTest extends \Flarum\<Ext>\Tests\integration\TestCase
 *     {
 *         use \Flarum\Audit\Tests\integration\InteractsWithAuditLog;
 *
 *         public function setUp(): void
 *         {
 *             parent::setUp();
 *             $this->setUpAuditLog();
 *             $this->extension('flarum-audit', 'flarum-<ext>');
 *             // ... seed data ...
 *         }
 *     }
 *
 * @method void extend(\Flarum\Extend\ExtenderInterface ...$extenders)
 * @method void prepareDatabase(array $data)
 * @method \Psr\Http\Message\ServerRequestInterface request(string $method, string $path, array $options = [])
 * @method ResponseInterface send(\Psr\Http\Message\ServerRequestInterface $request)
 */
trait InteractsWithAuditLog
{
    /**
     * Put the logger in test mode (so lifecycle events fired outside the test transaction
     * don't create stray entries), exempt the routes guests POST to during auth flows from
     * CSRF, and start from an empty audit log. Call from the consuming test's setUp() after
     * parent::setUp() and before enabling extensions / seeding.
     */
    protected function setUpAuditLog(): void
    {
        AuditLogger::$testMode = true;

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

    /**
     * Send an authenticated request and assert the response status. Echoes the body on a 422
     * so validation failures (which are otherwise logged nowhere) are easy to debug.
     */
    protected function sendSuccessfulRequest(string $method, string $path, array $options = [], int $statusCode = 200, ?int $authenticatedAs = 1): ResponseInterface
    {
        $response = $this->send($this->request($method, $path, $options + [
            'authenticatedAs' => $authenticatedAs,
        ]));

        if ($response->getStatusCode() === 422) {
            echo $response->getBody()->getContents();

            $response->getBody()->rewind();
        }

        $this->assertEquals($statusCode, $response->getStatusCode(), 'Assert request status code');

        return $response;
    }

    /**
     * Send a guest request to a route that's been exempted from CSRF in setUpAuditLog(),
     * avoiding the unreliable GET-token-then-POST dance.
     */
    protected function sendForumCsrfRequest(string $method, string $path, array $options = [], int $statusCode = 200): ResponseInterface
    {
        $response = $this->send($this->request($method, $path, $options));

        $this->assertEquals($statusCode, $response->getStatusCode(), 'Assert request status code');

        return $response;
    }

    /**
     * @param string|null $ip The IP the entry should record. Defaults to the test request IP;
     *                        pass null for actions logged outside an HTTP request (e.g. a CLI /
     *                        scheduled-task erasure), which legitimately have no IP.
     */
    protected function assertLogExists(string $action, ?array $payload = null, ?int $actorId = 1, ?int $skip = 0, ?string $ip = '127.0.0.1'): void
    {
        /** @var AuditLog $log */
        $log = AuditLog::query()->where('action', $action)->skip($skip)->first();

        $this->assertNotNull($log, 'Asserting log exists');

        $this->assertEquals($actorId, $log->actor_id, 'Asserting logged actor');

        $this->assertEquals($payload, $log->payload, 'Asserting logged payload');

        $this->assertEquals($ip, $log->ip_address, 'Asserting logged IP');
    }

    protected function assertLogDoesntExist(string $action): void
    {
        $log = AuditLog::query()->where('action', $action)->first();

        $this->assertNull($log, 'Asserting log doesn\'t exist');
    }
}
