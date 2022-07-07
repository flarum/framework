<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Approval\Tests\integration\api;

use Flarum\Approval\Tests\integration\InteractsWithUnapprovedContent;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Illuminate\Support\Arr;

class ListDiscussionsTest extends TestCase
{
    use RetrievesAuthorizedUsers;
    use InteractsWithUnapprovedContent;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-approval');

        $this->prepareUnapprovedDatabaseContent();
    }

    /**
     * @dataProvider unallowedUsers
     * @test
     */
    public function can_only_see_approved_if_not_allowed_to_approve(?int $authenticatedAs)
    {
        $response = $this->send(
            $this->request('GET', '/api/discussions', compact('authenticatedAs'))
        );

        $body = json_decode($response->getBody()->getContents(), true);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEqualsCanonicalizing([1, 4, 5, 7], Arr::pluck($body['data'], 'id'));
    }

    /**
     * @dataProvider allowedUsers
     * @test
     */
    public function can_see_unapproved_if_allowed_to_approve(int $authenticatedAs)
    {
        $response = $this->send(
            $this->request('GET', '/api/discussions', compact('authenticatedAs'))
        );

        $body = json_decode($response->getBody()->getContents(), true);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEqualsCanonicalizing([1, 2, 3, 4, 5, 6, 7], Arr::pluck($body['data'], 'id'));
    }
}
