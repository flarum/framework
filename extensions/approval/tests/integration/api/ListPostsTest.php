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

class ListPostsTest extends TestCase
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
     * @dataProvider userVisiblePostsDataProvider
     * @test
     */
    public function can_only_see_approved_if_allowed(?int $authenticatedAs, array $visiblePostIds)
    {
        $response = $this->send(
            $this
                ->request('GET', '/api/posts', compact('authenticatedAs'))
                ->withQueryParams([
                    'filter' => [
                        'discussion' => 7
                    ]
                ])
        );

        $body = json_decode($response->getBody()->getContents(), true);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEqualsCanonicalizing($visiblePostIds, Arr::pluck($body['data'], 'id'));
    }

    public function userVisiblePostsDataProvider(): array
    {
        return [
            // Admin can view unapproved posts.
            [1, [7, 8, 9, 10, 11, 12]],

            // User with approval perms can view unapproved posts.
            [3, [7, 8, 9, 10, 11, 12]],

            // Normal users cannot view unapproved posts unless being an author.
            [null, [7, 8, 10]],
            [2, [7, 8, 9, 10]],
            [4, [7, 8, 10, 11, 12]],
        ];
    }
}
