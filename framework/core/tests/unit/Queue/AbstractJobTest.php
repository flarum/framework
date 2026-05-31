<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\unit\Queue;

use Flarum\Queue\AbstractJob;
use Flarum\Testing\unit\TestCase;
use PHPUnit\Framework\Attributes\Test;

class AbstractJobTest extends TestCase
{
    protected function tearDown(): void
    {
        AbstractJobTestStub::$onQueue = null;
        AbstractJobTestSubclassStub::$onQueue = null;

        parent::tearDown();
    }

    #[Test]
    public function defaults_to_no_queue_routing(): void
    {
        $job = new AbstractJobTestStub();

        $this->assertNull($job->queue);
    }

    #[Test]
    public function routes_onto_queue_named_by_static_property(): void
    {
        AbstractJobTestStub::$onQueue = 'priority';

        $job = new AbstractJobTestStub();

        $this->assertSame('priority', $job->queue);
    }

    #[Test]
    public function static_property_is_resolved_via_late_static_binding(): void
    {
        AbstractJobTestStub::$onQueue = 'parent-queue';
        AbstractJobTestSubclassStub::$onQueue = 'child-queue';

        $this->assertSame('parent-queue', (new AbstractJobTestStub())->queue);
        $this->assertSame('child-queue', (new AbstractJobTestSubclassStub())->queue);
    }
}

class AbstractJobTestStub extends AbstractJob
{
    public static ?string $onQueue = null;
}

class AbstractJobTestSubclassStub extends AbstractJobTestStub
{
    public static ?string $onQueue = null;
}
