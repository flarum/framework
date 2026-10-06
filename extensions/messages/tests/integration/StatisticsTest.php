<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Messages\Tests\integration;

use Carbon\Carbon;
use Flarum\Messages\Dialog;
use Flarum\Messages\DialogMessage;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\Test;

/**
 * With flarum/statistics, admins see how much private messaging there is:
 * conversations started, and the replies that continue them.
 */
class StatisticsTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected Carbon $tenDaysAgo;
    protected Carbon $nineDaysAgo;
    protected Carbon $threeDaysAgo;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenDaysAgo = Carbon::now()->subDays(10)->startOfDay()->addHours(12);
        $this->nineDaysAgo = Carbon::now()->subDays(9)->startOfDay()->addHours(12);
        $this->threeDaysAgo = Carbon::now()->subDays(3)->startOfDay()->addHours(12);

        $this->extension('flarum-statistics', 'flarum-messages');

        $this->prepareDatabase([
            User::class => [
                $this->normalUser(),
                ['id' => 3, 'username' => 'alice', 'email' => 'alice@machine.local', 'is_email_confirmed' => 1],
            ],
            Dialog::class => [
                ['id' => 101, 'type' => 'direct', 'created_at' => $this->tenDaysAgo],
                ['id' => 102, 'type' => 'direct', 'created_at' => $this->threeDaysAgo],
            ],
            DialogMessage::class => [
                // Started ten days ago, replied to twice the next day.
                ['id' => 101, 'dialog_id' => 101, 'user_id' => 2, 'content' => 'Hi', 'number' => 1, 'created_at' => $this->tenDaysAgo],
                ['id' => 102, 'dialog_id' => 101, 'user_id' => 3, 'content' => 'Hello', 'number' => 2, 'created_at' => $this->nineDaysAgo],
                ['id' => 103, 'dialog_id' => 101, 'user_id' => 2, 'content' => 'How are you?', 'number' => 3, 'created_at' => $this->nineDaysAgo],
                // Started three days ago, no reply yet.
                ['id' => 104, 'dialog_id' => 102, 'user_id' => 3, 'content' => 'Hey', 'number' => 1, 'created_at' => $this->threeDaysAgo],
            ],
            'dialog_user' => [
                ['dialog_id' => 101, 'user_id' => 2, 'joined_at' => $this->tenDaysAgo],
                ['dialog_id' => 101, 'user_id' => 3, 'joined_at' => $this->tenDaysAgo],
                ['dialog_id' => 102, 'user_id' => 2, 'joined_at' => $this->threeDaysAgo],
                ['dialog_id' => 102, 'user_id' => 3, 'joined_at' => $this->threeDaysAgo],
            ],
        ]);
    }

    private function statistics(array $params): array
    {
        $response = $this->send($this->request('GET', '/api/statistics', ['authenticatedAs' => 1])->withQueryParams($params));

        $this->assertEquals(200, $response->getStatusCode(), (string) $response->getBody());

        return json_decode((string) $response->getBody(), true);
    }

    #[Test]
    public function the_totals_count_conversations_started_and_replies_sent(): void
    {
        $totals = $this->statistics(['period' => 'lifetime']);

        $this->assertSame(2, $totals['dialogs']);
        // A conversation's first message starts it; it isn't a reply.
        $this->assertSame(2, $totals['dialog_replies']);
    }

    #[Test]
    public function conversations_are_counted_by_the_day_they_started(): void
    {
        $this->assertEqualsCanonicalizing([
            $this->tenDaysAgo->copy()->startOfDay()->getTimestamp() => 1,
            $this->threeDaysAgo->copy()->startOfDay()->getTimestamp() => 1,
        ], $this->statistics(['model' => 'dialogs']));
    }

    #[Test]
    public function replies_are_counted_by_the_day_they_were_sent(): void
    {
        $this->assertEqualsCanonicalizing([
            $this->nineDaysAgo->copy()->startOfDay()->getTimestamp() => 2,
        ], $this->statistics(['model' => 'dialog_replies']));
    }

    #[Test]
    public function they_are_listed_after_the_built_in_statistics(): void
    {
        $forum = json_decode((string) $this->send($this->request('GET', '/api', ['authenticatedAs' => 1]))->getBody(), true)['data']['attributes'];

        $this->assertSame(['users', 'discussions', 'posts', 'dialogs', 'dialog_replies'], $forum['statisticsEntities']);
    }
}
