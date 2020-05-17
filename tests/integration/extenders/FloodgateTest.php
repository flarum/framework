<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\extenders;

use Flarum\Extend;
use Flarum\Tests\integration\RetrievesAuthorizedUsers;
use Flarum\Tests\integration\TestCase;

class FloodgateTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function prepDb(): void
    {
        $this->prepareDatabase([
            'users' => [
                $this->normalUser(),
            ],
            'groups' => [
                $this->memberGroup(),
            ],
            'group_user' => [
                ['user_id' => 2, 'group_id' => 3],
            ],
            'group_permission' => [
                ['permission' => 'viewDiscussions', 'group_id' => 3],
            ]
        ]);
    }

    /**
     * @test
     */
    public function list_discussions_not_restricted_by_default()
    {
        $this->prepDb();

        $response = $this->send($this->request('GET', '/api/discussions', ['authenticatedAs' => 2]));

        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function list_discussions_can_be_restricted()
    {
        $this->extend((new Extend\Floodgate)->set('blockListDiscussions', ['/api/discussions'], ['GET'], function ($actor, $request) {
            return true;
        }));
        $response = $this->send($this->request('GET', '/api/discussions', ['authenticatedAs' => 2]));

        $this->assertEquals(429, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function false_overrides_true_for_evaluating_floodgates()
    {
        $this->extend((new Extend\Floodgate)->set('blockListDiscussions', ['/api/discussions'], ['GET'], function ($actor, $request) {
            return true;
        }));
        $this->extend((new Extend\Floodgate)->set('blockListDiscussions', ['/api/discussions'], ['GET'], function ($actor, $request) {
            return false;
        }));

        $response = $this->send($this->request('GET', '/api/discussions', ['authenticatedAs' => 2]));

        $this->assertEquals(200, $response->getStatusCode());
    }
}
