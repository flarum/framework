<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\forum;

use Flarum\Foundation\Application;
use Flarum\Queue\QueueFactory;
use Flarum\Testing\integration\TestCase;
use Illuminate\Contracts\Queue\Factory;
use Illuminate\Queue\Worker;
use PHPUnit\Framework\Attributes\Test;

class UpgradePageTest extends TestCase
{
    protected function tearDown(): void
    {
        // The pausable flag is process-global; a test that flips it must not
        // leak that into the next one.
        Worker::$pausable = true;

        parent::tearDown();
    }

    private function factory(): QueueFactory
    {
        $factory = $this->app()->getContainer()->make(Factory::class);

        $this->assertInstanceOf(QueueFactory::class, $factory);

        return $factory;
    }

    #[Test]
    public function upgrade_page_returns_503_when_version_is_outdated(): void
    {
        $this->setting('version', '0.1.0');

        $response = $this->send(
            $this->request('GET', '/')
        );

        $this->assertEquals(503, $response->getStatusCode());
        $this->assertStringContainsString('Update Flarum', (string) $response->getBody());
    }

    #[Test]
    public function upgrade_page_is_not_shown_when_version_matches(): void
    {
        $this->setting('version', Application::VERSION);

        $response = $this->send(
            $this->request('GET', '/')
        );

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertStringNotContainsString('Update Flarum', (string) $response->getBody());
    }

    #[Test]
    public function serving_the_updater_pauses_the_queue(): void
    {
        $this->setting('version', '0.1.0');
        $this->config('queue', ['driver' => 'database']);

        $this->assertFalse($this->factory()->isPaused('flarum', '*'));

        $this->send($this->request('GET', '/'));

        // A worker must not run jobs against a schema that is about to change,
        // and the danger starts now — not when migrations eventually run.
        $this->assertTrue($this->factory()->isPaused('flarum', '*'));
    }

    #[Test]
    public function serving_the_normal_site_does_not_pause_the_queue(): void
    {
        $this->setting('version', Application::VERSION);
        $this->config('queue', ['driver' => 'database']);

        $this->send($this->request('GET', '/'));

        $this->assertFalse($this->factory()->isPaused('flarum', '*'));
    }

    #[Test]
    public function the_queue_is_not_paused_when_pausing_is_disabled(): void
    {
        // If workers are told not to poll for pause signals, writing the flag
        // is only misleading state — the same refusal `queue:pause` makes.
        $this->setting('version', '0.1.0');
        $this->config('queue', ['driver' => 'database']);

        Worker::$pausable = false;

        $this->send($this->request('GET', '/'));

        $this->assertFalse($this->factory()->isPaused('flarum', '*'));
    }
}
