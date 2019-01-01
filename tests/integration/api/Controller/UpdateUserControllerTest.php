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

class UpdateUserControllerTest extends ApiControllerTestCase
{
    protected $controller = UpdateUserController::class;

    protected $data = [
        'email' => 'newemail@machine.local',
    ];

    protected $userAttributes = [
        'username' => 'timtom',
        'password' => 'too-obscure',
        'email' => 'timtom@machine.local',
        'is_email_confirmed' => true,
    ];

    /**
     * @test
     */
    public function users_can_see_their_private_information()
    {
        $this->actor = $this->getNormalUser();
        $response = $this->callWith([], ['id' => $this->actor->id]);

        // Test for successful response and that the email is included in the response
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertContains('timtom@machine.local', (string) $response->getBody());
    }

    /**
     * @test
     */
    public function users_can_not_see_other_users_private_information()
    {
        $this->actor = $this->getNormalUser();

        $response = $this->callWith([], ['id' => 1]);

        // Make sure sensitive information is not made public
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertNotContains('admin@example.com', (string) $response->getBody());
    }

    public function tearDown()
    {
        parent::tearDown();
    }
}
