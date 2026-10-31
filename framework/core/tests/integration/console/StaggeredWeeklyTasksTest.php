<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\console;

use Flarum\Testing\integration\ConsoleTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

/**
 * Weekly tasks that call an outside service must not run on every forum at
 * the same moment, the start of the week.
 */
class StaggeredWeeklyTasksTest extends ConsoleTestCase
{
    public static function tasks(): array
    {
        return [
            'abandoned extensions list' => ['extensions:sync-abandoned'],
            'announcements' => ['announcements:refresh'],
        ];
    }

    #[Test]
    #[DataProvider('tasks')]
    public function the_task_runs_weekly_at_a_time_of_this_forums_own(string $command): void
    {
        $output = $this->runCommand(['command' => 'schedule:list']);

        $this->assertMatchesRegularExpression('/^\s*(\S+\s+){5}.*'.preg_quote($command).'/m', $output);

        preg_match('/^\s*((?:\S+\s+){5}).*'.preg_quote($command).'/m', $output, $matches);
        $expression = preg_replace('/\s+/', ' ', trim($matches[1]));

        $this->assertMatchesRegularExpression('/^\d{1,2} \d{1,2} \* \* [0-6]$/', $expression);
        $this->assertNotSame('0 0 * * 0', $expression);
    }
}
