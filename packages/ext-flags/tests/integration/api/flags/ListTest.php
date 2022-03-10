<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Flags\Tests\integration\api\flags;

use Flarum\Group\Group;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
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
            'users' => [
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
            'discussions' => [
                ['id' => 1, 'title' => '', 'user_id' => 1, 'comment_count' => 1],
            ],
            'posts' => [
                ['id' => 1, 'discussion_id' => 1, 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p></p></t>'],
                ['id' => 2, 'discussion_id' => 1, 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p></p></t>'],
                ['id' => 3, 'discussion_id' => 1, 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p></p></t>'],
            ],
            'flags' => [
                ['id' => 1, 'post_id' => 1, 'user_id' => 1],
                ['id' => 2, 'post_id' => 1, 'user_id' => 2],
                ['id' => 3, 'post_id' => 1, 'user_id' => 3],
                ['id' => 4, 'post_id' => 2, 'user_id' => 2],
                ['id' => 5, 'post_id' => 3, 'user_id' => 1],
            ]
        ]);
    }

    /**
     * @test
     */
    public function admin_can_see_one_flag_per_post()
    {
        $response = $this->send(
            $this->request('GET', '/api/flags', [
                'authenticatedAs' => 1
            ])
        );

        $this->assertEquals(200, $response->getStatusCode());

        $data = json_decode($response->getBody()->getContents(), true)['data'];

        $ids = Arr::pluck($data, 'id');
        $this->assertEqualsCanonicalizing(['1', '4', '5'], $ids);
    }

    /**
     * @test
     */
    public function regular_user_sees_own_flags()
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
    public function mod_can_see_one_flag_per_post()
    {
        $response = $this->send(
            $this->request('GET', '/api/flags', [
                'authenticatedAs' => 3
            ])
        );

        $this->assertEquals(200, $response->getStatusCode());

        $data = json_decode($response->getBody()->getContents(), true)['data'];

        $ids = Arr::pluck($data, 'id');
        $this->assertEqualsCanonicalizing(['1', '4', '5'], $ids);
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
