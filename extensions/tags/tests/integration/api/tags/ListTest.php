<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tags\Tests\integration\api\tags;

use Flarum\Group\Group;
use Flarum\Tags\Tag;
use Flarum\Tags\Tests\integration\RetrievesRepresentativeTags;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use Illuminate\Support\Arr;

class ListTest extends TestCase
{
    use RetrievesAuthorizedUsers;
    use RetrievesRepresentativeTags;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-tags');

        $this->prepareDatabase([
            Tag::class => $this->tags(),
            User::class => [
                $this->normalUser(),
            ],
            'group_permission' => [
                ['group_id' => Group::MEMBER_ID, 'permission' => 'tag8.viewForum'],
                ['group_id' => Group::MEMBER_ID, 'permission' => 'tag11.viewForum']
            ]
        ]);
    }

    /**
     * @test
     */
    public function admin_sees_all()
    {
        $response = $this->send(
            $this->request('GET', '/api/tags', [
                'authenticatedAs' => 1
            ])
        );

        $this->assertEquals(200, $response->getStatusCode());

        $data = json_decode($response->getBody()->getContents(), true)['data'];

        $ids = Arr::pluck($data, 'id');
        $this->assertEquals(['1', '2', '3', '4', '5', '6', '7', '8', '9', '10', '11', '12', '13', '14'], $ids);
    }

    /**
     * @test
     */
    public function user_sees_where_allowed()
    {
        $response = $this->send(
            $this->request('GET', '/api/tags', [
                'authenticatedAs' => 2
            ])
        );

        $this->assertEquals(200, $response->getStatusCode());

        $data = json_decode($response->getBody()->getContents(), true)['data'];

        // 5 isnt included because parent access doesnt necessarily give child access
        // 6, 7, 8 aren't included because child access shouldnt work unless parent
        // access is also given.
        $ids = Arr::pluck($data, 'id');
        $this->assertEquals(['1', '2', '3', '4', '9', '10', '11'], $ids);
    }

    /**
     * @dataProvider listTagsIncludesDataProvider
     * @test
     */
    public function user_sees_where_allowed_with_included_tags(string $include, array $expectedIncludes)
    {
        $response = $this->send(
            $this->request('GET', '/api/tags', [
                'authenticatedAs' => 2,
            ])->withQueryParams([
                'include' => $include
            ])
        );

        $this->assertEquals(200, $response->getStatusCode());

        $responseBody = json_decode($response->getBody()->getContents(), true);

        $data = $responseBody['data'];

        // 5 isnt included because parent access doesnt necessarily give child access
        // 6, 7, 8 aren't included because child access shouldnt work unless parent
        // access is also given.
        $this->assertEquals(['1', '2', '3', '4', '9', '10', '11'], Arr::pluck($data, 'id'));
        $this->assertEquals(
            $expectedIncludes,
            collect($data)
            ->pluck('relationships.'.$include.'.data')
            ->filter(fn ($data) => ! empty($data))
            ->values()
            ->flatMap(fn (array $data) => isset($data['type']) ? [$data] : $data)
            ->pluck('id')
            ->unique()
            ->all()
        );
    }

    /**
     * @test
     */
    public function guest_cant_see_restricted_or_children_of_restricted()
    {
        $response = $this->send(
            $this->request('GET', '/api/tags')
        );

        $this->assertEquals(200, $response->getStatusCode());

        $data = json_decode($response->getBody()->getContents(), true)['data'];

        $ids = Arr::pluck($data, 'id');
        $this->assertEquals(['1', '2', '3', '4', '9', '10'], $ids);
    }

    public function listTagsIncludesDataProvider(): array
    {
        return [
            ['children', ['3', '4']],
            ['parent', ['2']],
        ];
    }
}
