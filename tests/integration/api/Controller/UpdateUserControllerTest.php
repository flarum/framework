<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\api\Controller;

use Flarum\Api\Controller\UpdateUserController;
use Flarum\User\User;

class UpdateUserControllerTest extends ApiControllerTestCase
{
    protected $controller = UpdateUserController::class;

    protected $data = [
        'email' => 'newemail@machine.local',
    ];

    public function setUp()
    {
        parent::setUp();

        $this->prepareDatabase([
            'users' => [
                $this->adminUser(),
                $this->normalUser(),
            ],
            'groups' => [
                $this->adminGroup(),
                $this->memberGroup(),
            ],
            'group_user' => [
                ['user_id' => 1, 'group_id' => 1],
                ['user_id' => 2, 'group_id' => 3],
            ],
            'group_permission' => [
                ['permission' => 'viewUserList', 'group_id' => 3],
            ]
        ]);
    }

    /**
     * @test
     */
    public function users_can_see_their_private_information()
    {
        $this->actor = User::find(2);

        $response = $this->callWith([], ['id' => 2]);

        // Test for successful response and that the email is included in the response
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertContains('normal@machine.local', (string) $response->getBody());
    }

    /**
     * @test
     */
    public function users_can_not_see_other_users_private_information()
    {
        $this->actor = User::find(2);

        $response = $this->callWith([], ['id' => 1]);

        // Make sure sensitive information is not made public
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertNotContains('admin@machine.local', (string) $response->getBody());
    }
}
