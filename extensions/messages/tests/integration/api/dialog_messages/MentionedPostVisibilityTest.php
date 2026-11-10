<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Messages\Tests\integration\api\dialog_messages;

use Carbon\Carbon;
use Flarum\Discussion\Discussion;
use Flarum\Messages\Dialog;
use Flarum\Messages\DialogMessage;
use Flarum\Post\Post;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;

/**
 * The Index endpoint eager-loads all four mention relations, which used to
 * bypass the visibility scope applied by EloquentBuffer::load(): a post the
 * reader cannot see, mentioned in a dialog message, was serialised in full.
 */
class MentionedPostVisibilityTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-tags', 'flarum-messages', 'flarum-mentions');

        $this->prepareDatabase([
            User::class => [
                $this->normalUser(),
                ['id' => 4, 'username' => 'bob', 'email' => 'bob@machine.local', 'is_email_confirmed' => 1],
            ],
            Discussion::class => [
                ['id' => 50, 'title' => 'Hidden discussion', 'created_at' => Carbon::now()->toDateTimeString(), 'user_id' => 1, 'first_post_id' => 50, 'comment_count' => 1, 'is_private' => 1],
            ],
            Post::class => [
                ['id' => 50, 'number' => 1, 'discussion_id' => 50, 'created_at' => Carbon::now()->toDateTimeString(), 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p>restricted staff content</p></t>'],
            ],
            Dialog::class => [
                ['id' => 200, 'type' => 'direct'],
            ],
            DialogMessage::class => [
                ['id' => 200, 'dialog_id' => 200, 'user_id' => 4, 'content' => 'see this', 'number' => 1],
            ],
            'dialog_user' => [
                ['dialog_id' => 200, 'user_id' => 2, 'joined_at' => Carbon::now()],
                ['dialog_id' => 200, 'user_id' => 4, 'joined_at' => Carbon::now()],
            ],
            'dialog_message_mentions_post' => [
                ['dialog_message_id' => 200, 'mentions_post_id' => 50],
            ],
        ]);
    }

    public function test_a_mentioned_post_the_reader_cannot_see_is_not_serialised(): void
    {
        $response = $this->send(
            $this->request('GET', '/api/dialog-messages?filter[dialog]=200&include=mentionsPosts', [
                'authenticatedAs' => 2,
            ])
        );

        $this->assertEquals(200, $response->getStatusCode(), (string) $response->getBody());
        $this->assertStringNotContainsString('restricted staff content', (string) $response->getBody());
    }
}
