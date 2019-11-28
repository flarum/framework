<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\api\Controller;

use Flarum\Api\Controller\ListGroupsController;
use Flarum\Group\Group;

class ListGroupsControllerTest extends ApiControllerTestCase
{
    protected $controller = ListGroupsController::class;

    /**
     * @test
     */
    public function shows_index_for_guest()
    {
        $response = $this->callWith();

        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getBody()->getContents(), true);

        $this->assertEquals(Group::count(), count($data['data']));
    }
}
