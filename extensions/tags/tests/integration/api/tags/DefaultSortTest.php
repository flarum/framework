<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tags\Tests\integration\api\tags;

use Flarum\Tags\Tag;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\Test;

/**
 * A tag can name the sort its discussion list opens with.
 *
 * The value stored is the alias that appears in a URL — `newest`, `top`, and
 * whatever else extensions have added — rather than the API sort behind it, so
 * that it round-trips through the sort dropdown unchanged.
 *
 * Nothing validates that the alias still exists. Sorts arrive and leave with
 * the extensions that register them, and a tag pointing at one that has gone
 * should fall back quietly rather than break the page or lose the setting: the
 * extension may well be reinstalled tomorrow.
 */
class DefaultSortTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-tags');

        $this->prepareDatabase([
            User::class => [
                $this->normalUser(),
            ],
            Tag::class => [
                ['id' => 1, 'name' => 'Support', 'slug' => 'support', 'position' => 0],
                ['id' => 2, 'name' => 'Archive', 'slug' => 'archive', 'position' => 1, 'default_sort' => 'oldest'],
            ],
        ]);
    }

    protected function update(int $id, array $attributes, int $actor = 1)
    {
        return $this->send(
            $this->request('PATCH', "/api/tags/$id", [
                'authenticatedAs' => $actor,
                'json' => [
                    'data' => ['attributes' => $attributes],
                ],
            ])
        );
    }

    #[Test]
    public function an_admin_can_set_a_default_sort()
    {
        $response = $this->update(1, ['defaultSort' => 'newest']);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('newest', Tag::find(1)->default_sort);
    }

    #[Test]
    public function an_admin_can_clear_a_default_sort()
    {
        $response = $this->update(2, ['defaultSort' => null]);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertNull(Tag::find(2)->default_sort);
    }

    #[Test]
    public function a_normal_user_cannot_set_a_default_sort()
    {
        $response = $this->update(1, ['defaultSort' => 'newest'], 2);

        $this->assertEquals(403, $response->getStatusCode());
        $this->assertNull(Tag::find(1)->default_sort);
    }

    #[Test]
    public function the_default_sort_is_visible_in_the_api()
    {
        $response = $this->send(
            $this->request('GET', '/api/tags', ['authenticatedAs' => 1])
        );

        $body = json_decode($response->getBody()->getContents(), true);

        $archive = collect($body['data'])->firstWhere('id', '2');

        $this->assertEquals('oldest', $archive['attributes']['defaultSort']);
    }

    #[Test]
    public function a_tag_with_no_default_sort_reports_null_rather_than_omitting_it()
    {
        // The admin UI needs to tell "no preference" apart from "attribute
        // missing", so the field is always present.
        $response = $this->send(
            $this->request('GET', '/api/tags', ['authenticatedAs' => 1])
        );

        $body = json_decode($response->getBody()->getContents(), true);

        $support = collect($body['data'])->firstWhere('id', '1');

        $this->assertArrayHasKey('defaultSort', $support['attributes']);
        $this->assertNull($support['attributes']['defaultSort']);
    }

    #[Test]
    public function a_sort_that_no_longer_exists_is_kept_rather_than_rejected()
    {
        // The extension that registered `trending` may be disabled today and
        // enabled again next week. Refusing the value, or clearing it, would
        // lose a setting the administrator never changed.
        $response = $this->update(1, ['defaultSort' => 'trending']);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('trending', Tag::find(1)->default_sort);
    }
}
