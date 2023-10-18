<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\extenders;

use Flarum\Console\AbstractCommand;
use Flarum\Extend;
use Flarum\Testing\integration\ConsoleTestCase;
use Illuminate\Console\Scheduling\Event;

class ConsoleTest extends ConsoleTestCase
{
    /**
     * @test
     */
    public function custom_command_doesnt_exist_by_default()
    {
        $input = [
            'command' => 'customTestCommand'
        ];

        $this->assertEquals('Command "customTestCommand" is not defined.', $this->runCommand($input));
    }

    /**
     * @test
     */
    public function custom_command_exists_when_added()
    {
        $this->extend(
            (new Extend\Console())
                ->command(CustomCommand::class)
        );

        $input = [
            'command' => 'customTestCommand'
        ];

        $this->assertEquals('Custom Command.', $this->runCommand($input));
    }

    /**
     * @test
     */
    public function scheduled_command_doesnt_exist_by_default()
    {
        $input = [
            'command' => 'schedule:list'
        ];

        $this->assertStringNotContainsString('cache:clear', $this->runCommand($input));
    }

    /**
     * @test
     */
    public function scheduled_command_exists_when_added()
    {
        $this->extend(
            (new Extend\Console())
                ->schedule('cache:clear', function (Event $event) {
                    $event->everyMinute();
                })
        );

        $input = [
            'command' => 'schedule:list'
        ];

        $this->assertStringContainsString('cache:clear', $this->runCommand($input));
    }

    /**
     * @test
     */
    public function scheduled_command_exists_when_added_with_class_syntax()
    {
        $this->extend(
            (new Extend\Console())
                ->schedule('cache:clear', ScheduledCommandCallback::class)
        );

        $input = [
            'command' => 'schedule:list'
        ];

        $this->assertStringContainsString('cache:clear', $this->runCommand($input));
    }
}

class CustomCommand extends AbstractCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('customTestCommand');
    }

    /**
     * {@inheritdoc}
     */
    protected function fire()
    {
        $this->info('Custom Command.');
    }
}

class ScheduledCommandCallback
{
    public function __invoke(Event $event)
    {
        $event->everyMinute();
    }
}
