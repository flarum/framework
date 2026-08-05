<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\console;

use Flarum\Testing\integration\ConsoleTestCase;
use Illuminate\Console\Events\CommandFinished;
use Illuminate\Console\Scheduling\ScheduleRunCommand;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Contracts\Events\Dispatcher;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

class ScheduleRunTest extends ConsoleTestCase
{
    private function cache(): CacheRepository
    {
        return $this->app()->getContainer()->make(CacheRepository::class);
    }

    /**
     * The admin info screen reports when the scheduler last ran, from a cache
     * key a `CommandFinished` listener writes. The listener recognises the run
     * by the name the event carries, so if the two ever disagree the key is
     * never written and the forum reports that the scheduler has never run.
     *
     * `Flarum\Console\Server` dispatches that event with the command's own
     * `getName()`, which is what this feeds in. The name used to be read back
     * through `Command::getDefaultName()`, removed in Symfony 8.
     */
    #[Test]
    public function the_scheduler_finishing_records_when_it_last_ran(): void
    {
        $this->app();

        $this->cache()->forget('flarum:schedule:last_run');

        $name = (new ScheduleRunCommand())->getName();

        $this->app()->getContainer()->make(Dispatcher::class)->dispatch(
            new CommandFinished($name, new ArrayInput([]), new BufferedOutput(), 0)
        );

        $this->assertNotNull(
            $this->cache()->get('flarum:schedule:last_run'),
            "A finished '$name' did not record flarum:schedule:last_run."
        );
    }

    /**
     * Any other command finishing must leave the timestamp alone, or the info
     * screen would report a scheduler run whenever anything at all was run.
     */
    #[Test]
    public function another_command_finishing_does_not_record_a_scheduler_run(): void
    {
        $this->app();

        $this->cache()->forget('flarum:schedule:last_run');

        $this->app()->getContainer()->make(Dispatcher::class)->dispatch(
            new CommandFinished('cache:clear', new ArrayInput([]), new BufferedOutput(), 0)
        );

        $this->assertNull($this->cache()->get('flarum:schedule:last_run'));
    }

    /**
     * The listener matches on a literal, and the name is Laravel's to change.
     * Reading it back from the `#[AsCommand]` attribute keeps the two honest —
     * and that attribute is the only way to ask, now that Symfony 8 has removed
     * `Command::getDefaultName()`.
     */
    #[Test]
    public function the_scheduler_is_still_named_what_the_listener_expects(): void
    {
        $attributes = (new \ReflectionClass(ScheduleRunCommand::class))->getAttributes(AsCommand::class);

        $this->assertNotEmpty($attributes, 'ScheduleRunCommand no longer declares an #[AsCommand] attribute.');
        $this->assertSame('schedule:run', $attributes[0]->newInstance()->name);
        $this->assertSame('schedule:run', (new ScheduleRunCommand())->getName());
    }
}
