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
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

class ShowTest extends TestCase
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

    #[Test]
    public function can_show_tag_with_url_decoded_utf8_slug()
    {
        $this->prepareDatabase([
            Tag::class => [
                ['id' => 155, 'name' => '测试', 'slug' => '测试', 'position' => 0, 'parent_id' => null]
            ]
        ]);

        $response = $this->send(
            $this->request('GET', '/api/tags/测试')
        );

        $this->assertEquals(200, $response->getStatusCode());

        $response2 = $this->send(
            $this->request('GET', '/t/测试')
        );

        $this->assertEquals(200, $response2->getStatusCode());
    }

    #[Test]
    public function can_show_tag_with_url_encoded_utf8_slug()
    {
        $this->prepareDatabase([
            Tag::class => [
                ['id' => 155, 'name' => '测试', 'slug' => '测试', 'position' => 0, 'parent_id' => null]
            ]
        ]);

        $response = $this->send(
            $this->request('GET', '/api/tags/'.urlencode('测试'))
        );

        $this->assertEquals(200, $response->getStatusCode());

        $response2 = $this->send(
            $this->request('GET', '/t/'.urlencode('测试'))
        );

        $this->assertEquals(200, $response2->getStatusCode());
    }

    #[Test]
    #[DataProvider('showTagIncludes')]
    public function user_sees_tag_relations_where_allowed(string $include, array $expectedIncludes)
    {
        $response = $this->send(
            $this->request('GET', '/api/tags/primary-2-child-2', [
                'authenticatedAs' => 2,
            ])->withQueryParams([
                'include' => $include
            ])
        );

        $this->assertEquals(200, $response->getStatusCode());

        $responseBody = json_decode($response->getBody()->getContents(), true);

        $included = $responseBody['included'] ?? [];
        $this->assertEqualsCanonicalizing($expectedIncludes, Arr::pluck($included, 'id'));
    }

    public static function showTagIncludes(): array
    {
        return [
            ['children', []],
            ['parent', ['2']],
            ['parent.children', ['3', '2']],
            ['parent.children.parent', ['3', '2']],
        ];
    }
}
