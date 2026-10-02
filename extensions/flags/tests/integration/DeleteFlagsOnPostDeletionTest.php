<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Flags\Tests\integration;

use Carbon\Carbon;
use Flarum\Discussion\Discussion;
use Flarum\Extend;
use Flarum\Flags\Flag;
use Flarum\Post\Post;
use Flarum\Testing\integration\TestCase;
use PHPUnit\Framework\Attributes\Test;

class DeleteFlagsOnPostDeletionTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-flags');

        $date = Carbon::parse('2021-01-01T12:00:00+00:00');

        $this->prepareDatabase([
            Discussion::class => [
                ['id' => 10, 'title' => 'A', 'created_at' => $date, 'last_posted_at' => $date, 'first_post_id' => 1, 'last_post_id' => 2, 'last_post_number' => 2, 'comment_count' => 2],
            ],
            Post::class => [
                ['id' => 1, 'number' => 1, 'discussion_id' => 10, 'created_at' => $date, 'type' => 'comment', 'content' => '<t><p>A</p></t>'],
                ['id' => 2, 'number' => 2, 'discussion_id' => 10, 'created_at' => $date, 'type' => 'comment', 'content' => '<t><p>B</p></t>'],
            ],
            Flag::class => [
                ['id' => 20, 'post_id' => 2],
                ['id' => 21, 'post_id' => 2],
            ],
        ]);
    }

    #[Test]
    public function deleting_a_flagged_post_fires_model_events_for_its_flags()
    {
        $deleted = [];

        // The listener must delete the flags through Eloquent before the post row
        // is gone — otherwise the flags.post_id cascade removes them at the
        // database level and this event never fires.
        $this->extend((new Extend\Event())->listen('eloquent.deleted: '.Flag::class, function (Flag $flag) use (&$deleted) {
            $deleted[] = $flag->id;
        }));

        $response = $this->send($this->request('DELETE', '/api/posts/2', ['authenticatedAs' => 1]));

        $this->assertEquals(204, $response->getStatusCode());

        $this->assertEqualsCanonicalizing([20, 21], $deleted);
        $this->assertSame(0, Flag::query()->count());
    }
}
