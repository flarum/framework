<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tags\Tests\integration\api\tags;

use Flarum\Group\Group;
use Flarum\Tags\Tests\integration\RetrievesRepresentativeTags;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Illuminate\Support\Arr;

class ShowTest extends TestCase
{
    use RetrievesAuthorizedUsers;
    use RetrievesRepresentativeTags;

    /**
     * {@inheritDoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-tags');

        $this->prepareDatabase([
            'tags' => $this->tags(),
            'users' => [
                $this->normalUser(),
            ],
            'group_permission' => [
                ['group_id' => Group::MEMBER_ID, 'permission' => 'tag8.viewForum'],
                ['group_id' => Group::MEMBER_ID, 'permission' => 'tag11.viewForum'],
            ],
        ]);
    }

    /**
     * @dataProvider showTagIncludes
     * @test
     */
    public function user_sees_tag_relations_where_allowed(string $include, array $expectedIncludes)
    {
        $response = $this->send(
            $this->request('GET', '/api/tags/primary-2-child-2', [
                'authenticatedAs' => 2,
            ])->withQueryParams([
                'include' => $include,
            ])
        );

        $this->assertEquals(200, $response->getStatusCode());

        $responseBody = json_decode($response->getBody()->getContents(), true);

        $included = $responseBody['included'] ?? [];
        $this->assertEqualsCanonicalizing($expectedIncludes, Arr::pluck($included, 'id'));
    }

    public function showTagIncludes(): array
    {
        return [
            ['children', []],
            ['parent', ['2']],
            ['parent.children', ['3', '2']],
            ['parent.children.parent', ['3', '2']],
        ];
    }
}
