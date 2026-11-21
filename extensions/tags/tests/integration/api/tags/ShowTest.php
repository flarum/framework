<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tags\Tests\integration\api\tags;

use Flarum\Group\Group;
use Flarum\Tags\IdWithSlugDriver;
use Flarum\Tags\Tag;
use Flarum\Tags\Testing\RetrievesRepresentativeTags;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use Illuminate\Database\ConnectionInterface;
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
    #[DataProvider('idWithSlugForms')]
    public function id_with_slug_driver_resolves_both_id_and_id_dash_slug(string $slug): void
    {
        // Like the discussion slugger, /t/123-the-slug and /t/123 are both valid:
        // the leading id resolves the tag and the trailing text is cosmetic.
        $this->setting('slug_driver_'.Tag::class, 'id_with_slug');

        $response = $this->send(
            $this->request('GET', "/api/tags/$slug")
        );

        $this->assertEquals(200, $response->getStatusCode(), "/api/tags/$slug");

        $body = json_decode($response->getBody()->getContents(), true);
        $this->assertSame('1', $body['data']['id']);
        // The canonical slug the driver advertises is always `<id>-<slug>`.
        $this->assertSame('1-primary-1', $body['data']['attributes']['slug']);

        // The frontend content route accepts the same forms.
        $contentResponse = $this->send(
            $this->request('GET', "/t/$slug")
        );

        $this->assertEquals(200, $contentResponse->getStatusCode(), "/t/$slug");
    }

    public static function idWithSlugForms(): array
    {
        return [
            'bare id' => ['1'],
            'id with matching slug' => ['1-primary-1'],
            'id with arbitrary cosmetic text' => ['1-anything-here'],
        ];
    }

    #[Test]
    public function id_with_slug_driver_returns_not_found_for_unknown_id(): void
    {
        $this->setting('slug_driver_'.Tag::class, 'id_with_slug');

        $response = $this->send(
            $this->request('GET', '/api/tags/9999-nope')
        );

        $this->assertEquals(404, $response->getStatusCode());
    }

    #[Test]
    #[DataProvider('outOfRangeIds')]
    public function id_with_slug_driver_returns_not_found_for_an_id_beyond_the_integer_range(string $slug): void
    {
        $this->setting('slug_driver_'.Tag::class, 'id_with_slug');

        $response = $this->send(
            $this->request('GET', "/api/tags/$slug")
        );

        $this->assertEquals(404, $response->getStatusCode(), (string) $response->getBody());

        $contentResponse = $this->send(
            $this->request('GET', "/t/$slug")
        );

        $this->assertEquals(404, $contentResponse->getStatusCode(), "/t/$slug");
    }

    public static function outOfRangeIds(): array
    {
        return [
            'bare id' => ['2147483648'],
            'id with slug' => ['2147483648-tag'],
            'id beyond bigint' => ['99999999999999999999'],
        ];
    }

    #[Test]
    public function numeric_slug_beyond_the_integer_range_still_finds_its_tag(): void
    {
        $this->prepareDatabase([
            Tag::class => [
                ['id' => 156, 'name' => 'Big number', 'slug' => '2147483648', 'position' => 0, 'parent_id' => null]
            ]
        ]);

        $response = $this->send(
            $this->request('GET', '/api/tags/2147483648')
        );

        $this->assertEquals(200, $response->getStatusCode(), (string) $response->getBody());
        $this->assertSame('156', json_decode((string) $response->getBody(), true)['data']['id']);
    }

    #[Test]
    public function batched_slugs_read_the_key_column_type_once(): void
    {
        $this->setting('slug_driver_'.Tag::class, 'id_with_slug');
        $this->app();

        $queries = 0;
        $db = resolve(ConnectionInterface::class);
        $db->listen(function ($query) use (&$queries) {
            if (str_contains($query->sql, 'pg_attribute')) {
                $queries++;
            }
        });

        $slugs = ['2147483648', '2147483649', '2147483650', '2147483651'];
        $found = resolve(IdWithSlugDriver::class)->fromSlugs($slugs, User::find(1));

        $this->assertCount(0, $found);
        $this->assertLessThanOrEqual(1, $queries);
    }

    #[Test]
    public function numeric_but_non_integer_id_is_not_found(): void
    {
        $response = $this->send(
            $this->request('GET', '/api/tags/1.5')
        );

        $this->assertEquals(404, $response->getStatusCode(), (string) $response->getBody());
    }

    #[Test]
    public function admin_sees_stored_slug_attribute(): void
    {
        $response = $this->send(
            $this->request('GET', '/api/tags/primary-1', ['authenticatedAs' => 1])
        );

        $this->assertSame(200, $response->getStatusCode());

        $attributes = json_decode($response->getBody()->getContents(), true)['data']['attributes'];

        $this->assertSame('primary-1', $attributes['storedSlug']);
        $this->assertSame($attributes['slug'], $attributes['storedSlug']);
    }

    #[Test]
    public function stored_slug_always_reflects_raw_column_regardless_of_active_driver(): void
    {
        $this->setting('slug_driver_'.Tag::class, 'id_with_slug');

        $response = $this->send(
            $this->request('GET', '/api/tags/1', ['authenticatedAs' => 1])
        );

        $this->assertSame(200, $response->getStatusCode());

        $attributes = json_decode($response->getBody()->getContents(), true)['data']['attributes'];

        $this->assertSame('1-primary-1', $attributes['slug']);

        $this->assertSame('primary-1', $attributes['storedSlug']);
    }

    #[Test]
    public function stored_slug_is_not_exposed_to_users_without_edit_permission(): void
    {
        $response = $this->send(
            $this->request('GET', '/api/tags/primary-1', ['authenticatedAs' => 2])
        );

        $this->assertSame(200, $response->getStatusCode());

        $attributes = json_decode($response->getBody()->getContents(), true)['data']['attributes'];
        $this->assertArrayNotHasKey('storedSlug', $attributes);
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
