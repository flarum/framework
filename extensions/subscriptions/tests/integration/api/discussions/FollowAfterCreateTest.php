<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Subscriptions\Tests\integration\api\discussions;

use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\Test;

class FollowAfterCreateTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-subscriptions');

        $this->prepareDatabase([
            User::class => [
                $this->normalUser(),
                ['id' => 3, 'username' => 'acme_follow', 'email' => 'acme@machine.local', 'is_email_confirmed' => 1, 'preferences' => json_encode(['followAfterCreate' => true])],
                ['id' => 4, 'username' => 'acme_no_follow', 'email' => 'acme2@machine.local', 'is_email_confirmed' => 1, 'preferences' => json_encode(['followAfterCreate' => false])],
            ],
        ]);
    }

    #[Test]
    public function user_with_preference_true_follows_after_creating_discussion()
    {
        $this->app();

        $response = $this->send(
            $this->request('POST', '/api/discussions', [
                'authenticatedAs' => 3,
                'json' => [
                    'data' => [
                        'type' => 'discussions',
                        'attributes' => [
                            'title' => 'Test Discussion',
                            'content' => 'Test content that needs to be sufficiently long.'
                        ],
                    ],
                ],
            ])
        );

        $this->assertEquals(201, $response->getStatusCode());

        $discussionId = json_decode($response->getBody()->getContents(), true)['data']['id'];

        $this->assertEquals('follow', $this->database()->table('discussion_user')->where('discussion_id', $discussionId)->where('user_id', 3)->value('subscription'));
    }

    #[Test]
    public function user_with_preference_false_does_not_follow_after_creating_discussion()
    {
        $this->app();

        $response = $this->send(
            $this->request('POST', '/api/discussions', [
                'authenticatedAs' => 4,
                'json' => [
                    'data' => [
                        'type' => 'discussions',
                        'attributes' => [
                            'title' => 'Test Discussion',
                            'content' => 'Test content that needs to be sufficiently long.'
                        ],
                    ],
                ],
            ])
        );

        $this->assertEquals(201, $response->getStatusCode());

        $discussionId = json_decode($response->getBody()->getContents(), true)['data']['id'];

        $this->assertNull($this->database()->table('discussion_user')->where('discussion_id', $discussionId)->where('user_id', 4)->value('subscription'));
    }
}
