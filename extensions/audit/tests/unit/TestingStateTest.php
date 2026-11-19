<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Audit\Tests\unit;

use Flarum\Audit\AuditLogger;
use Flarum\Audit\Testing\BuildsAuditRequests;
use Flarum\Audit\Testing\InteractsWithAuditLog;
use Illuminate\Database\ConnectionInterface;
use LogicException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use stdClass;

class TestingStateTest extends TestCase
{
    protected bool $originalMode;

    protected function setUp(): void
    {
        $this->originalMode = AuditLogger::$testMode;
    }

    protected function tearDown(): void
    {
        AuditLogger::$testMode = $this->originalMode;
    }

    public static function previousModes(): array
    {
        return [[false], [true]];
    }

    #[Test]
    #[DataProvider('previousModes')]
    public function setup_and_cleanup_restore_the_previous_mode_even_after_repeated_calls(bool $previous): void
    {
        AuditLogger::$testMode = $previous;
        $helper = new AuditHelperProbe();

        $helper->start();
        $helper->start();
        $this->assertTrue(AuditLogger::$testMode);
        $this->assertSame([], $helper->extenders);

        $helper->finish();
        $helper->finish();
        $this->assertSame($previous, AuditLogger::$testMode);
    }

    #[Test]
    public function cleanup_without_setup_leaves_the_mode_alone(): void
    {
        AuditLogger::$testMode = true;
        (new AuditHelperProbe())->finish();

        $this->assertTrue(AuditLogger::$testMode);
    }

    #[Test]
    public function the_public_base_restores_state_when_parent_teardown_throws(): void
    {
        AuditLogger::$testMode = false;
        $helper = new FailingAuditTestCase('unused');
        $helper->setUp();
        $this->assertTrue(AuditLogger::$testMode);

        try {
            $helper->finish();
            $this->fail('Parent cleanup must fail.');
        } catch (LogicException $error) {
            $this->assertSame('Parent cleanup failed', $error->getMessage());
        }

        $this->assertFalse(AuditLogger::$testMode);
    }

    #[Test]
    public function setup_refuses_an_already_booted_application_without_changing_the_mode(): void
    {
        AuditLogger::$testMode = false;
        $helper = new AuditHelperProbe();
        $helper->app = new stdClass();

        try {
            $helper->start();
            $this->fail('Late setup must fail.');
        } catch (LogicException) {
            $this->assertFalse(AuditLogger::$testMode);
        }
    }

    #[Test]
    public function csrf_exemptions_are_an_explicit_preboot_opt_in(): void
    {
        $helper = new AuditHelperProbe();
        $helper->exempt();
        $this->assertCount(1, $helper->extenders);

        $helper->app = new stdClass();
        $this->expectException(LogicException::class);
        $helper->exempt();
    }
}

class AuditHelperProbe
{
    use InteractsWithAuditLog;
    use BuildsAuditRequests;

    public ?object $app = null;
    public array $extenders = [];

    public function start(): void
    {
        $this->setUpAuditLog();
    }

    public function finish(): void
    {
        $this->tearDownAuditLog();
    }

    public function exempt(): void
    {
        $this->exemptAuditAuthRoutesFromCsrf();
    }

    public function extend(object ...$extenders): void
    {
        $this->extenders = array_merge($this->extenders, $extenders);
    }
}

class FailingAuditTestCase extends \Flarum\Audit\Testing\TestCase
{
    public function finish(): void
    {
        parent::tearDown();
    }

    protected function database(): ConnectionInterface
    {
        throw new LogicException('Parent cleanup failed');
    }
}
