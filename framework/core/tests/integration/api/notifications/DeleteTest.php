<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\api\notifications;

use Carbon\Carbon;
use Flarum\Discussion\Discussion;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

class DeleteTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->prepareDatabase([
            User::class => [
                $this->normalUser(),
            ],
            Discussion::class => [
                ['id' => 1, 'title' => 'Test Discussion', 'user_id' => 2, 'comment_count' => 1],
                ['id' => 2, 'title' => 'Test Discussion', 'user_id' => 2, 'comment_count' => 1],
                ['id' => 3, 'title' => 'Test Discussion', 'user_id' => 1, 'comment_count' => 1],
                ['id' => 4, 'title' => 'Test Discussion', 'user_id' => 1, 'comment_count' => 1],
            ],
            'notifications' => [
                ['id' => 1, 'user_id' => 1, 'type' => 'discussionRenamed', 'subject_id' => 1, 'from_user_id' => 2, 'read_at' => Carbon::now(), 'created_at' => Carbon::now()],
                ['id' => 2, 'user_id' => 1, 'type' => 'discussionRenamed', 'subject_id' => 2, 'from_user_id' => 2, 'read_at' => null, 'created_at' => Carbon::now()],
                ['id' => 3, 'user_id' => 2, 'type' => 'discussionRenamed', 'subject_id' => 3, 'from_user_id' => 1, 'read_at' => Carbon::now(), 'created_at' => Carbon::now()],
                ['id' => 4, 'user_id' => 2, 'type' => 'discussionRenamed', 'subject_id' => 4, 'from_user_id' => 1, 'read_at' => null, 'created_at' => Carbon::now()],
            ],
        ]);
    }

    #[Test]
    #[DataProvider('canDeleteAllNotifications')]
    public function user_can_delete_all_notifications(int $authenticatedAs)
    {
        $this->app();

        $this->assertEquals(2, User::query()->find($authenticatedAs)->notifications()->count());

        $response = $this->send(
            $this->request('DELETE', '/api/notifications', compact('authenticatedAs')),
        );

        $this->assertEquals(204, $response->getStatusCode());
        $this->assertEquals(0, User::query()->find($authenticatedAs)->notifications()->count());
    }

    public static function canDeleteAllNotifications(): array
    {
        return [
            [1],
            [2]
        ];
    }
}
