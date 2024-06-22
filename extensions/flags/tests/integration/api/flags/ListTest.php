<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Flags\Tests\integration\api\flags;

use Carbon\Carbon;
use Flarum\Discussion\Discussion;
use Flarum\Flags\Flag;
use Flarum\Group\Group;
use Flarum\Post\Post;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use Illuminate\Database\PostgresConnection;
use Illuminate\Support\Arr;

class ListTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-flags');

        $this->prepareDatabase([
            User::class => [
                $this->normalUser(),
                [
                    'id' => 3,
                    'username' => 'mod',
                    'password' => '$2y$10$LO59tiT7uggl6Oe23o/O6.utnF6ipngYjvMvaxo1TciKqBttDNKim', // BCrypt hash for "too-obscure"
                    'email' => 'normal2@machine.local',
                    'is_email_confirmed' => 1,
                ]
            ],
            'group_user' => [
                ['group_id' => Group::MODERATOR_ID, 'user_id' => 3]
            ],
            'group_permission' => [
                ['group_id' => Group::MODERATOR_ID, 'permission' => 'discussion.viewFlags'],
            ],
            Discussion::class => [
                ['id' => 1, 'title' => '', 'user_id' => 1, 'comment_count' => 1],
            ],
            Post::class => [
                ['id' => 1, 'discussion_id' => 1, 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p></p></t>'],
                ['id' => 2, 'discussion_id' => 1, 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p></p></t>'],
                ['id' => 3, 'discussion_id' => 1, 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p></p></t>'],
                ['id' => 4, 'discussion_id' => 1, 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p></p></t>', 'is_private' => true],
            ],
            Flag::class => [
                ['id' => 1, 'post_id' => 1, 'user_id' => 1, 'created_at' => Carbon::now()->addMinutes(2)],
                ['id' => 2, 'post_id' => 1, 'user_id' => 2, 'created_at' => Carbon::now()->addMinutes(3)],
                ['id' => 3, 'post_id' => 1, 'user_id' => 3, 'created_at' => Carbon::now()->addMinutes(4)],
                ['id' => 4, 'post_id' => 2, 'user_id' => 2, 'created_at' => Carbon::now()->addMinutes(5)],
                ['id' => 5, 'post_id' => 3, 'user_id' => 1, 'created_at' => Carbon::now()->addMinutes(6)],
                ['id' => 6, 'post_id' => 4, 'user_id' => 1, 'created_at' => Carbon::now()->addMinutes(7)],
            ]
        ]);
    }

    /**
     * @test
     */
    public function admin_can_see_one_flag_per_visible_post()
    {
        $response = $this->send(
            $this->request('GET', '/api/flags', [
                'authenticatedAs' => 1
            ])
        );

        $body = $response->getBody()->getContents();

        $this->assertEquals(200, $response->getStatusCode(), $body);

        $data = json_decode($body, true)['data'];

        $ids = Arr::pluck($data, 'id');

        if ($this->database() instanceof PostgresConnection) {
            $this->assertEqualsCanonicalizing(['3', '4', '5'], $ids);
        } else {
            $this->assertEqualsCanonicalizing(['1', '4', '5'], $ids);
        }
    }

    /**
     * @test
     */
    public function regular_user_sees_own_flags_of_visible_posts()
    {
        $response = $this->send(
            $this->request('GET', '/api/flags', [
                'authenticatedAs' => 2
            ])
        );

        $this->assertEquals(200, $response->getStatusCode());

        $data = json_decode($response->getBody()->getContents(), true)['data'];

        $ids = Arr::pluck($data, 'id');
        $this->assertEqualsCanonicalizing(['2', '4'], $ids);
    }

    /**
     * @test
     */
    public function mod_can_see_one_flag_per_visible_post()
    {
        $response = $this->send(
            $this->request('GET', '/api/flags', [
                'authenticatedAs' => 3
            ])
        );

        $this->assertEquals(200, $response->getStatusCode());

        $data = json_decode($response->getBody()->getContents(), true)['data'];

        $ids = Arr::pluck($data, 'id');
        $this->assertCount(3, $data);
    }

    /**
     * @test
     */
    public function guest_cant_see_flags()
    {
        $response = $this->send(
            $this->request('GET', '/api/flags')
        );

        $this->assertEquals(401, $response->getStatusCode());
    }
}
