<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\api\Controller;

use Flarum\Api\Controller\ListNotificationsController;
use Flarum\User\User;

class ListNotificationsControllerTest extends ApiControllerTestCase
{
    protected $controller = ListNotificationsController::class;

    public function setUp()
    {
        parent::setUp();

        $this->prepareDatabase([
            'users' => [
                $this->normalUser(),
            ],
        ]);
    }

    /**
     * @test
     */
    public function disallows_index_for_guest()
    {
        $response = $this->callWith();

        $this->assertEquals(401, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function show_index_for_user()
    {
        $this->actor = User::find(2);

        $response = $this->callWith();

        $this->assertEquals(200, $response->getStatusCode());
    }
}
