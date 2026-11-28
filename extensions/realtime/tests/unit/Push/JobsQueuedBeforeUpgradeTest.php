<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Realtime\Tests\unit\Push;

use Flarum\Messages\DialogMessage;
use Flarum\Realtime\Push\Jobs\SendDialogMessageJob;
use Flarum\Realtime\Push\Jobs\SendGeneratedPayloadJob;
use Flarum\Testing\unit\TestCase;
use Flarum\User\User;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use PHPUnit\Framework\Attributes\Test;
use ReflectionClass;

/**
 * A job waiting in the queue when the forum is upgraded comes back without
 * the properties added since: unserialising never runs the constructor.
 */
class JobsQueuedBeforeUpgradeTest extends TestCase
{
    /**
     * @template T of object
     * @param class-string<T> $class
     * @return T
     */
    private function queuedBefore(string $class, array $properties): object
    {
        $reflection = new ReflectionClass($class);
        $job = $reflection->newInstanceWithoutConstructor();

        foreach ($properties as $name => $value) {
            $reflection->getProperty($name)->setValue($job, $value);
        }

        return $job;
    }

    #[Test]
    public function a_payload_job_queued_before_the_upgrade_still_runs_its_middleware(): void
    {
        $message = (new DialogMessage())->forceFill(['id' => 7]);
        $recipient = (new User())->forceFill(['id' => 3]);

        $job = $this->queuedBefore(SendGeneratedPayloadJob::class, ['event' => 'created', 'model' => $message, 'recipient' => $recipient, 'includes' => null]);

        $this->assertInstanceOf(WithoutOverlapping::class, $job->middleware()[0]);
        $this->assertSame('created:'.SendGeneratedPayloadJob::class.':dialog_messages-7:3', $job->middleware()[0]->key);
    }

    #[Test]
    public function jobs_queued_before_the_socket_exclusion_leave_nobody_out(): void
    {
        $post = (new \Flarum\Post\Post())->forceFill(['id' => 7]);
        $recipient = (new User())->forceFill(['id' => 3]);

        $trigger = $this->queuedBefore(\Flarum\Realtime\Push\Jobs\SendTriggerJob::class, ['event' => 'posted', 'model' => $post, 'actor' => $recipient]);
        $payload = $this->queuedBefore(SendGeneratedPayloadJob::class, ['event' => 'posted', 'model' => $post, 'recipient' => $recipient, 'includes' => null]);

        $this->assertNull((new \ReflectionProperty($trigger, 'originSocket'))->getValue($trigger));
        $this->assertNull((new \ReflectionProperty($payload, 'exceptSocket'))->getValue($payload));
    }

    #[Test]
    public function a_dialog_job_queued_before_the_upgrade_still_runs_its_middleware(): void
    {
        $message = (new DialogMessage())->forceFill(['id' => 7]);

        $job = $this->queuedBefore(SendDialogMessageJob::class, ['event' => 'created', 'model' => $message]);

        $this->assertInstanceOf(WithoutOverlapping::class, $job->middleware()[0]);
        $this->assertSame('created:dialog_messages.7', $job->middleware()[0]->key);
    }
}
