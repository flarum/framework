<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\unit\Queue;

use Flarum\Queue\Console\DatabaseWorkerArgs;
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\Testing\unit\TestCase;
use PHPUnit\Framework\Attributes\Test;

class DatabaseWorkerArgsTest extends TestCase
{
    private function mockSettings(array $settings): SettingsRepositoryInterface
    {
        $mock = $this->createMock(SettingsRepositoryInterface::class);
        $mock->method('get')->willReturnCallback(function ($key) use ($settings) {
            return $settings[$key] ?? null;
        });

        return $mock;
    }

    #[Test]
    public function it_always_includes_stop_when_empty()
    {
        $settings = $this->mockSettings([]);
        $workerArgs = new DatabaseWorkerArgs($settings);

        $args = $workerArgs->args();

        $this->assertContains('--stop-when-empty', $args);
    }

    #[Test]
    public function it_adds_retries_when_setting_exists()
    {
        $settings = $this->mockSettings(['database-queue.retries' => '5']);
        $workerArgs = new DatabaseWorkerArgs($settings);

        $args = $workerArgs->args();

        $this->assertArrayHasKey('--tries', $args);
        $this->assertEquals('5', $args['--tries']);
    }

    #[Test]
    public function it_adds_memory_when_setting_exists()
    {
        $settings = $this->mockSettings(['database-queue.memory' => '256']);
        $workerArgs = new DatabaseWorkerArgs($settings);

        $args = $workerArgs->args();

        $this->assertArrayHasKey('--memory', $args);
        $this->assertEquals('256', $args['--memory']);
    }

    #[Test]
    public function it_adds_timeout_when_setting_exists()
    {
        $settings = $this->mockSettings(['database-queue.timeout' => '120']);
        $workerArgs = new DatabaseWorkerArgs($settings);

        $args = $workerArgs->args();

        $this->assertArrayHasKey('--timeout', $args);
        $this->assertEquals('120', $args['--timeout']);
    }

    #[Test]
    public function it_adds_rest_when_setting_exists()
    {
        $settings = $this->mockSettings(['database-queue.rest' => '5']);
        $workerArgs = new DatabaseWorkerArgs($settings);

        $args = $workerArgs->args();

        $this->assertArrayHasKey('--rest', $args);
        $this->assertEquals('5', $args['--rest']);
    }

    #[Test]
    public function it_adds_backoff_when_setting_exists()
    {
        $settings = $this->mockSettings(['database-queue.backoff' => '10']);
        $workerArgs = new DatabaseWorkerArgs($settings);

        $args = $workerArgs->args();

        $this->assertArrayHasKey('--backoff', $args);
        $this->assertEquals('10', $args['--backoff']);
    }

    #[Test]
    public function it_does_not_add_arguments_for_missing_settings()
    {
        $settings = $this->mockSettings([]);
        $workerArgs = new DatabaseWorkerArgs($settings);

        $args = $workerArgs->args();

        $this->assertArrayNotHasKey('--tries', $args);
        $this->assertArrayNotHasKey('--memory', $args);
        $this->assertArrayNotHasKey('--timeout', $args);
        $this->assertArrayNotHasKey('--rest', $args);
        $this->assertArrayNotHasKey('--backoff', $args);
    }

    #[Test]
    public function it_adds_all_arguments_when_all_settings_exist()
    {
        $settings = $this->mockSettings([
            'database-queue.retries' => '3',
            'database-queue.memory' => '512',
            'database-queue.timeout' => '90',
            'database-queue.rest' => '2',
            'database-queue.backoff' => '5',
        ]);
        $workerArgs = new DatabaseWorkerArgs($settings);

        $args = $workerArgs->args();

        $this->assertContains('--stop-when-empty', $args);
        $this->assertEquals('3', $args['--tries']);
        $this->assertEquals('512', $args['--memory']);
        $this->assertEquals('90', $args['--timeout']);
        $this->assertEquals('2', $args['--rest']);
        $this->assertEquals('5', $args['--backoff']);
    }
}
