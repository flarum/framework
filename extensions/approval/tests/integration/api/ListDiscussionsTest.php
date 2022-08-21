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
     * @dataProvider userVisibleDiscussionsDataProvider
     * @test
     */
    public function can_only_see_approved_if_allowed(?int $authenticatedAs, array $visibleDiscussionIds)
    {
        $response = $this->send(
            $this->request('GET', '/api/discussions', compact('authenticatedAs'))
        );

        $body = json_decode($response->getBody()->getContents(), true);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEqualsCanonicalizing($visibleDiscussionIds, Arr::pluck($body['data'], 'id'));
    }

    public function userVisibleDiscussionsDataProvider(): array
    {
        return [
            'admin can view unapproved discussions' => [1, [1, 2, 3, 4, 5, 6, 7, 8]],
            'user with perms can view unapproved discussions' => [3, [1, 2, 3, 4, 5, 6, 7, 8]],
            'guests cannot view unapproved discussions' => [null, [1, 4, 5, 7]],
            'normal users cannot view unapproved discussions unless being an author 1' => [2, [1, 4, 5, 6, 7]],
            'normal users cannot view unapproved discussions unless being an author 2' => [4, [1, 2, 3, 4, 5, 7, 8]],
        ];
    }
}
