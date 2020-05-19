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

class ThrottleApiTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function prepDb(): void
    {
        $this->prepareDatabase([
            'users' => [
                $this->normalUser(),
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
        $this->extend((new Extend\ThrottleApi)->set('blockListDiscussions', function ($request) {
            if ($request->getAttribute('routeName') === 'discussions.index') {
                return true;
            }
        }));

        $this->prepDb();

        $response = $this->send($this->request('GET', '/api/discussions', ['authenticatedAs' => 2]));

        $this->assertEquals(429, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function false_overrides_true_for_evaluating_throttlers()
    {
        $this->extend(
            (new Extend\ThrottleApi)->set('blockListDiscussions', function ($request) {
                if ($request->getAttribute('routeName') === 'discussions.index') {
                    return true;
                }
            }),
            (new Extend\ThrottleApi)->set('blockListDiscussionsOverride', function ($request) {
                if ($request->getAttribute('routeName') === 'discussions.index') {
                    return false;
                }
            })
        );

        $this->prepDb();

        $this->prepDb();

        $response = $this->send($this->request('GET', '/api/discussions', ['authenticatedAs' => 2]));

        $this->assertEquals(200, $response->getStatusCode());
    }
}
