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

use Flarum\Api\Controller\ListUsersController;

class ListUsersControllerTest extends ApiControllerTestCase
{
    protected $controller = ListUsersController::class;

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
    public function shows_index_for_admin()
    {
        $this->actor = $this->getAdminUser();

        $response = $this->callWith();

        $this->assertEquals(200, $response->getStatusCode());
    }
}
