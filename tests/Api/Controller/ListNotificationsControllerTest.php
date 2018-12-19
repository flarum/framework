<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Tests\Api\Controller;

use Flarum\Api\Controller\ListNotificationsController;

class ListNotificationsControllerTest extends ApiControllerTestCase
{
    protected $controller = ListNotificationsController::class;

    /**
     * @test
     * @expectedException \Flarum\User\Exception\PermissionDeniedException
     */
    public function disallows_index_for_guest()
    {
        $this->callWith();
    }

    /**
     * @test
     */
    public function show_index_for_user()
    {
        $this->actor = $this->getNormalUser();

        $response = $this->callWith();

        $this->assertEquals(200, $response->getStatusCode());
    }
}
