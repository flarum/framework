<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tags\Tests\integration\api\tags;

use Flarum\Api\Context;
use Flarum\Api\Schema;
use Flarum\Extend;
use Flarum\Tags\Api\Resource\TagResource;
use Flarum\Tags\Tag;
use Flarum\Tags\Tests\integration\RetrievesRepresentativeTags;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Listing tags loads the actor's state for each of them together, as showing
 * one does, so a field that reads it (as fof/follow-tags' subscription does)
 * doesn't look it up tag by tag.
 */
class ListTagStateTest extends TestCase
{
    use RetrievesAuthorizedUsers;
    use RetrievesRepresentativeTags;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-tags');

        $this->extend(
            (new Extend\ApiResource(TagResource::class))
                ->fields(fn () => [
                    Schema\Boolean::make('hiddenByActor')
                        ->get(fn (Tag $tag, Context $context) => (bool) $tag->stateFor($context->getActor())->is_hidden),
                ])
        );

        $this->prepareDatabase([
            Tag::class => array_merge($this->tags(), [
                ['id' => 20, 'name' => 'Alpha', 'slug' => 'alpha', 'position' => 20, 'parent_id' => null],
                ['id' => 21, 'name' => 'Beta', 'slug' => 'beta', 'position' => 21, 'parent_id' => null],
                ['id' => 22, 'name' => 'Kid of Alpha', 'slug' => 'kid-of-alpha', 'position' => 0, 'parent_id' => 20],
                ['id' => 23, 'name' => 'Kid of Beta', 'slug' => 'kid-of-beta', 'position' => 0, 'parent_id' => 21],
            ]),
            User::class => [
                $this->normalUser(),
                ['id' => 3, 'username' => 'other', 'email' => 'other@machine.local', 'is_email_confirmed' => 1],
            ],
            'tag_user' => [
                ['user_id' => 2, 'tag_id' => 1, 'is_hidden' => 1],
                ['user_id' => 3, 'tag_id' => 1, 'is_hidden' => 0],
                ['user_id' => 3, 'tag_id' => 2, 'is_hidden' => 1],
                ['user_id' => 3, 'tag_id' => 20, 'is_hidden' => 1],
            ],
        ]);
    }

    /**
     * One query for the listed tags' states, and one for their parents'.
     */
    #[Test]
    public function listing_tags_looks_up_the_actors_state_for_them_together()
    {
        [$tags, $stateQueries] = $this->list($this->request('GET', '/api/tags', ['authenticatedAs' => 2]));

        $this->assertGreaterThan(5, count($tags));
        $this->assertLessThanOrEqual(2, $stateQueries);
    }

    #[Test]
    public function each_listed_tag_carries_the_actors_own_state()
    {
        [$forNormal] = $this->list($this->request('GET', '/api/tags', ['authenticatedAs' => 2]));
        [$forOther] = $this->list($this->request('GET', '/api/tags', ['authenticatedAs' => 3]));

        $this->assertEquals(['1' => true, '2' => false], array_intersect_key($forNormal, ['1' => 0, '2' => 0]));
        $this->assertEquals(['1' => false, '2' => true], array_intersect_key($forOther, ['1' => 0, '2' => 0]));
    }

    #[Test]
    public function a_guest_has_no_state()
    {
        [$tags, $stateQueries] = $this->list($this->request('GET', '/api/tags'));

        $this->assertNotEmpty($tags);
        $this->assertSame([], array_filter($tags));
        $this->assertLessThanOrEqual(2, $stateQueries);
    }

    /**
     * A search can list children without their parents, which then come as
     * included tags: one query for the listed tags' states, one for theirs.
     */
    #[Test]
    public function tags_included_as_the_listed_tags_parents_carry_the_actors_own_state()
    {
        [$tags, $stateQueries] = $this->list(
            $this->request('GET', '/api/tags', ['authenticatedAs' => 3])->withQueryParams(['filter' => ['q' => 'Kid'], 'include' => 'parent'])
        );

        $this->assertEquals(['20' => true, '21' => false, '22' => false, '23' => false], array_intersect_key($tags, array_flip(['20', '21', '22', '23'])));
        $this->assertCount(4, $tags);
        $this->assertLessThanOrEqual(2, $stateQueries);
    }

    /**
     * Each tag in the response, data and included, with whether the actor
     * hides it; and how many queries looked up tag states.
     *
     * @return array{array<string, bool>, int}
     */
    protected function list(ServerRequestInterface $request): array
    {
        $database = $this->database();
        $database->flushQueryLog();
        $database->enableQueryLog();

        try {
            $response = $this->send($request);
            $queries = array_column($database->getQueryLog(), 'query');
        } finally {
            $database->disableQueryLog();
            $database->flushQueryLog();
        }

        $this->assertEquals(200, $response->getStatusCode(), (string) $response->getBody());

        $document = json_decode((string) $response->getBody(), true);
        $tags = [];

        foreach (array_merge($document['data'], $document['included'] ?? []) as $resource) {
            if ($resource['type'] === 'tags') {
                $tags[$resource['id']] = $resource['attributes']['hiddenByActor'];
            }
        }

        return [$tags, count(array_filter($queries, fn (string $sql) => str_contains($sql, 'tag_user')))];
    }
}
