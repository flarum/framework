<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\extenders;

use Flarum\Extend;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Illuminate\Http\Request;

class ThrottleApiTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        parent::setUp();

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
        $response = $this->send($this->request('GET', '/api/discussions', ['authenticatedAs' => 2]));

        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function list_discussions_can_be_restricted()
    {
        $this->extend((new Extend\ThrottleApi)->set('blockListDiscussions', function (Request $request) {
            if ($request->routeIs('api.discussions.index')) {
                return true;
            }
        }));

        $response = $this->send($this->request('GET', '/api/discussions', ['authenticatedAs' => 2]));

        $this->assertEquals(429, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function false_overrides_true_for_evaluating_throttlers()
    {
        $this->extend(
            (new Extend\ThrottleApi)->set('blockListDiscussions', function (Request $request) {
                if ($request->routeIs('api.discussions.index')) {
                    return true;
                }
            }),
            (new Extend\ThrottleApi)->set('blockListDiscussionsOverride', function (Request $request) {
                if ($request->routeIs('api.discussions.index')) {
                    return false;
                }
            })
        );

        $response = $this->send($this->request('GET', '/api/discussions', ['authenticatedAs' => 2]));

        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function throttling_applies_to_api_client()
    {
        $this->extend((new Extend\ThrottleApi)->set('blockRegistration', function (Request $request) {
            if ($request->routeIs('api.users.create')) {
                return true;
            }
        }));

        $response = $this->send(
            tap(
                $this->request('POST', '/register'),
                fn (Request $request) => $request->attributes->set('bypassCsrfToken', true)
            )
        );

        $this->assertEquals(429, $response->getStatusCode());
    }
}
