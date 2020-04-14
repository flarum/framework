<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\api\groups;

use Flarum\Group\Group;
use Flarum\Tests\integration\RetrievesAuthorizedUsers;
use Flarum\Tests\integration\TestCase;
use Flarum\User\User;

class ListTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected $controller = ListGroupsController::class;

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
                $this->hiddenGroup()
            ],
            'group_user' => [
                ['user_id' => 1, 'group_id' => 1],
            ],
        ]);
    }

    /**
     * @test
     */
    public function shows_limited_index_for_guest()
    {
        $response = $this->send(
            $this->request('GET', '/api/groups')
        );

        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getBody()->getContents(), true);

        $this->assertEquals(Group::where('is_hidden', 0)->count(), count($data['data']));
    }

    /**
     * @test
     */
    public function shows_index_for_admin()
    {
        $this->actor = User::find(1);
        $response = $this->callWith();

        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getBody()->getContents(), true);

        $this->assertEquals(Group::count(), count($data['data']));
    }

    protected function hiddenGroup(): array
    {
        return [
            'id' => 10,
            'name_singular' => 'Hidden',
            'name_plural' => 'Ninjas',
            'color' => null,
            'icon' => 'fas fa-wrench',
            'is_hidden' => 1
        ];
    }
}
