<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Subscriptions\Tests\integration\api\discussions;

use Carbon\Carbon;
use Flarum\Discussion\Discussion;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\Test;

/**
 * `POST /api/discussions/read` upserts read state in bulk, naming only the
 * read columns, so a row an extension shares — flarum/subscriptions keeps its
 * `subscription` here — must keep its own columns through the write.
 *
 * This lives with the extension rather than in core because the column it
 * guards is the extension's, and the core test that once checked it added the
 * column with an `ALTER TABLE` inside the test transaction, which MySQL and
 * MariaDB commit implicitly and so leaked fixtures into later tests.
 */
class MarkAllAsReadKeepsSubscriptionTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-subscriptions');

        $this->prepareDatabase([
            User::class => [
                $this->normalUser(),
            ],
            Discussion::class => [
                // Authored by the admin (id 1), visible to the actor.
                ['id' => 1, 'title' => __CLASS__, 'created_at' => Carbon::now(), 'last_posted_at' => Carbon::now(), 'user_id' => 1, 'comment_count' => 1, 'last_post_number' => 5],
            ],
            'discussion_user' => [
                // The actor (normal, id 2) follows the discussion and has read
                // only part of it, so it still matches `unread` and the bulk
                // upsert will write its read columns.
                ['discussion_id' => 1, 'user_id' => 2, 'last_read_post_number' => 1, 'subscription' => 'follow'],
            ],
        ]);
    }

    #[Test]
    public function marking_read_in_bulk_keeps_the_subscription_on_the_row()
    {
        $response = $this->send(
            $this->request('POST', '/api/discussions/read', ['authenticatedAs' => 2])
                ->withQueryParams(['filter' => ['author' => 'admin']])
        );

        $this->assertEquals(204, $response->getStatusCode(), (string) $response->getBody());

        $row = $this->database()->table('discussion_user')->where(['user_id' => 2, 'discussion_id' => 1])->first();

        // The read columns the endpoint owns are updated...
        $this->assertEquals(5, $row->last_read_post_number);

        // ...and the subscription the extension keeps on the same row survives.
        $this->assertEquals('follow', $row->subscription);
    }
}
