<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Audit\Testing;

use Flarum\Audit\AuditLog;
use Flarum\Audit\AuditLogger;
use LogicException;

/**
 * Call setup before the app boots, and cleanup after parent::tearDown() in a finally block.
 *
 * @phpstan-require-extends \Flarum\Testing\integration\TestCase
 */
trait InteractsWithAuditLog
{
    protected ?bool $previousAuditTestMode = null;

    protected function setUpAuditLog(): void
    {
        if ($this->app !== null) {
            throw new LogicException('Configure Audit testing before the application boots.');
        }

        $this->previousAuditTestMode ??= AuditLogger::$testMode;
        AuditLogger::$testMode = true;
    }

    protected function tearDownAuditLog(): void
    {
        if ($this->previousAuditTestMode !== null) {
            AuditLogger::$testMode = $this->previousAuditTestMode;
            $this->previousAuditTestMode = null;
        }
    }

    /**
     * Payload matching is complete; null expects a null payload. Null actor/IP cover CLI actions.
     * Entries of the same action are selected in ascending ID order.
     */
    protected function assertLogExists(string $action, ?array $payload = null, ?int $actorId = 1, ?int $skip = 0, ?string $ip = '127.0.0.1'): void
    {
        /** @var AuditLog|null $log */
        $log = AuditLog::query()->where('action', $action)->orderBy('id')->skip($skip ?? 0)->first();

        $this->assertNotNull($log, 'Asserting log exists');

        if ($actorId === null) {
            $this->assertNull($log->actor_id, 'Asserting logged actor');
        } else {
            $this->assertNotNull($log->actor_id, 'Asserting logged actor');
            $this->assertEquals($actorId, $log->actor_id, 'Asserting logged actor');
        }

        if ($payload === null) {
            $this->assertNull($log->payload, 'Asserting logged payload');
        } else {
            $this->assertIsArray($log->payload, 'Asserting logged payload');
            $this->assertEquals($payload, $log->payload, 'Asserting logged payload');
        }

        $this->assertSame($ip, $log->ip_address, 'Asserting logged IP');
    }

    protected function assertLogDoesntExist(string $action): void
    {
        $this->assertFalse(AuditLog::query()->where('action', $action)->exists(), 'Asserting log does not exist');
    }
}
