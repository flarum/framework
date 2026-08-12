<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\admin;

use Flarum\Extend;
use Flarum\Api\Sort\SortColumn;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\Test;

/**
 * The sort options a resource offers, published to the admin frontend.
 *
 * The forum already receives this — it is how the sort dropdown is built and
 * how `?sort=newest` in a URL becomes `-createdAt` for the API. The admin side
 * needs the same list to offer sorting as a setting, and building that list in
 * JavaScript instead would mean a second copy that silently omits whatever
 * sorts extensions have added.
 *
 * Keyed by resource type rather than hardcoded to discussions, since posts and
 * users have sort maps of their own.
 */
class SortMapsPayloadTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->prepareDatabase([
            'users' => [
                $this->normalUser(),
            ],
        ]);
    }

    protected function payload(): array
    {
        $response = $this->send(
            $this->request('GET', '/admin', ['authenticatedAs' => 1])
        );

        preg_match('/<script id="flarum-json-payload" type="application\/json">(.+?)<\/script>/s', (string) $response->getBody(), $matches);

        $this->assertNotEmpty($matches, 'The admin page carried no JSON payload.');

        return json_decode(html_entity_decode($matches[1]), true);
    }

    #[Test]
    public function payload_carries_sort_maps_keyed_by_resource_type()
    {
        $maps = $this->payload()['sortMaps'];

        $this->assertArrayHasKey('discussions', $maps);

        // Only discussions ship aliased sorts in core. Users, for instance, can
        // be sorted through the API but none of those sorts are named, so there
        // is nothing to publish — see the test below.
        $this->assertIsArray($maps['discussions']);
    }

    #[Test]
    public function a_sort_map_maps_the_alias_to_the_api_sort()
    {
        $discussions = $this->payload()['sortMaps']['discussions'];

        $this->assertEquals('-lastPostedAt', $discussions['latest']);
        $this->assertEquals('-createdAt', $discussions['newest']);
        $this->assertEquals('createdAt', $discussions['oldest']);
    }

    #[Test]
    public function resources_without_sorts_are_left_out_entirely()
    {
        // An empty map would have the admin offering a control with nothing in
        // it, so those resources are omitted rather than published empty.
        foreach ($this->payload()['sortMaps'] as $type => $map) {
            $this->assertNotEmpty($map, "The sort map for $type is empty and should not have been published.");
        }
    }

    #[Test]
    public function a_sort_added_by_an_extension_is_published_too()
    {
        // The reason this belongs on the server: an extension adding a sort
        // gets it into the admin UI without touching the admin frontend.
        $this->extend(
            (new Extend\ApiResource(\Flarum\Api\Resource\DiscussionResource::class))
                ->sorts(fn () => [
                    SortColumn::make('participantCount')
                        ->ascendingAlias('fewest_people')
                        ->descendingAlias('most_people'),
                ])
        );

        $discussions = $this->payload()['sortMaps']['discussions'];

        $this->assertArrayHasKey('most_people', $discussions);
        $this->assertEquals('-participantCount', $discussions['most_people']);
        $this->assertEquals('participantCount', $discussions['fewest_people']);
    }

    #[Test]
    public function sorts_without_an_alias_are_not_published()
    {
        // A sort with no alias is usable through the API but has no name to
        // show in a dropdown, so it has nothing to contribute here.
        $this->extend(
            (new Extend\ApiResource(\Flarum\Api\Resource\DiscussionResource::class))
                ->sorts(fn () => [
                    SortColumn::make('slug'),
                ])
        );

        $this->assertArrayNotHasKey('slug', $this->payload()['sortMaps']['discussions']);
    }

    #[Test]
    public function a_resource_whose_sorts_are_all_unaliased_is_absent()
    {
        // Users can be sorted through the API, but core gives none of those
        // sorts an alias, so there is nothing to name in a dropdown and the
        // resource does not appear at all.
        $this->assertArrayNotHasKey('users', $this->payload()['sortMaps']);
    }

    #[Test]
    public function a_normal_user_never_sees_this()
    {
        // Belt and braces: the admin payload is admin-only, and this test
        // fails loudly if that ever stops being true.
        $response = $this->send(
            $this->request('GET', '/admin', ['authenticatedAs' => 2])
        );

        $this->assertEquals(403, $response->getStatusCode());
    }
}
