<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\unit\Extension;

use Flarum\Extension\Console\WeeklySchedule;
use Flarum\Testing\unit\TestCase;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\EventMutex;
use PHPUnit\Framework\Attributes\Test;

class WeeklyScheduleTest extends TestCase
{
    private function schedule(?string $seed): Event
    {
        $event = new Event($this->createStub(EventMutex::class), 'php flarum extensions:sync-abandoned');

        (new WeeklySchedule($seed))($event);

        return $event;
    }

    #[Test]
    public function each_forum_runs_it_once_a_week_at_a_time_of_its_own(): void
    {
        $expression = $this->schedule('https://discuss.flarum.org')->expression;

        $this->assertMatchesRegularExpression('/^\d{1,2} \d{1,2} \* \* [0-6]$/', $expression);
        $this->assertSame($expression, $this->schedule('https://discuss.flarum.org')->expression);
        $this->assertNotSame($expression, $this->schedule('https://example.com')->expression);
    }

    #[Test]
    public function forums_are_spread_over_the_week(): void
    {
        $expressions = array_map(fn (int $i) => $this->schedule("https://forum$i.example.com")->expression, range(1, 50));

        $this->assertCount(50, array_unique($expressions));
        $this->assertGreaterThan(1, count(array_unique(array_map(fn (string $expression) => substr($expression, -1), $expressions))));
    }

    #[Test]
    public function it_does_not_overlap_itself(): void
    {
        $this->assertTrue($this->schedule('https://discuss.flarum.org')->withoutOverlapping);
    }

    #[Test]
    public function without_a_seed_it_runs_at_the_start_of_the_week(): void
    {
        $this->assertSame('0 0 * * 0', $this->schedule(null)->expression);
    }
}
