<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\api;

use Carbon\Carbon;
use Flarum\Api\Endpoint;
use Flarum\Api\Endpoint\Concerns\HasEagerLoading;
use Flarum\Api\JsonApi;
use Flarum\Api\Resource\AbstractDatabaseResource;
use Flarum\Api\Resource\DiscussionResource;
use Flarum\Api\Schema\Relationship\ToMany;
use Flarum\Api\Schema\Relationship\ToOne;
use Flarum\Discussion\Discussion;
use Flarum\Extend;
use Flarum\Post\Post;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\Test;
use ReflectionProperty;

/**
 * A relation named in an endpoint's eager-load list is pre-loaded with
 * loadMissing(), which bypasses EloquentBuffer::load() — the only place a
 * related resource's scope() (whereVisibleTo) would otherwise be applied. If
 * such a relation is also serialisable, it reaches the client with no
 * visibility check at all.
 *
 * HasEagerLoading::scopeEagerLoads() closes that for every path it loads. This
 * test guards the invariant rather than the two relations that were reported:
 * a newly added eager load — in core or in an extension — is covered the moment
 * it is registered, and fails here if the mechanism is ever bypassed.
 */
class EagerLoadedRelationsAreScopedTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    /**
     * Every relationship path any endpoint eager-loads, as
     * "resource type => endpoint => path".
     *
     * @return array<string, string[]>
     */
    private function eagerLoadedPaths(JsonApi $api): array
    {
        $paths = [];

        foreach ($api->resources as $type => $resource) {
            if (! $resource instanceof AbstractDatabaseResource) {
                continue;
            }

            foreach ($resource->endpoints() as $endpoint) {
                if (! in_array(HasEagerLoading::class, class_uses_recursive($endpoint), true)) {
                    continue;
                }

                foreach ($this->declaredRelations($endpoint) as $relation) {
                    $paths[$type][] = $relation;
                }
            }
        }

        return $paths;
    }

    /**
     * The statically declared eager loads of an endpoint. Closure-valued
     * entries are skipped: they depend on the request's includes, and the
     * relations they return are covered by the same scoping code path.
     *
     * @return string[]
     */
    private function declaredRelations(object $endpoint): array
    {
        $relations = [];

        foreach (['loadRelations', 'loadRelationWhere'] as $property) {
            if (! property_exists($endpoint, $property)) {
                continue;
            }

            $reflection = new ReflectionProperty($endpoint, $property);
            $reflection->setAccessible(true);
            $value = $reflection->getValue($endpoint);

            foreach ($value as $key => $entry) {
                // eagerLoadWhere() keys by relation name; eagerLoad() appends
                // either a path or a closure.
                $relation = is_string($key) ? $key : $entry;

                if (is_string($relation)) {
                    $relations[] = $relation;
                }
            }
        }

        return array_unique($relations);
    }

    /**
     * Both eager-load routes — eagerLoad() and eagerLoadWhere() — must apply
     * the related resource's scope.
     *
     * Proved end to end: an endpoint is given an eager load of a relation
     * whose resource scope excludes everything, and the relation must come
     * back empty. A bare loadMissing() would return the record regardless,
     * which is the defect this guards.
     */
    #[Test]
    public function an_eager_loaded_relation_honours_its_resource_scope()
    {
        // A relation that is eager-loaded but NOT in defaultInclude: it reaches
        // the response only through the eager-load path, so if that path is
        // unscoped the hidden post is disclosed.
        $this->extend(
            (new Extend\ApiResource(DiscussionResource::class))
                ->endpoint(Endpoint\Show::class, fn (Endpoint\Show $endpoint) => $endpoint->eagerLoad(['firstPost']))
        );

        $this->prepareDatabase([
            Discussion::class => [
                ['id' => 80, 'title' => 'scoped', 'created_at' => Carbon::now()->toDateTimeString(), 'user_id' => 2, 'first_post_id' => 80, 'comment_count' => 1, 'is_private' => 0],
            ],
            Post::class => [
                ['id' => 80, 'number' => 1, 'discussion_id' => 80, 'created_at' => Carbon::now()->toDateTimeString(), 'user_id' => 2, 'type' => 'comment', 'content' => '<t><p>EXCLUDED-BY-SCOPE</p></t>', 'hidden_at' => Carbon::now()->toDateTimeString()],
            ],
            User::class => [$this->normalUser()],
        ]);

        $response = $this->send($this->request('GET', '/api/discussions/80'));
        $body = (string) $response->getBody();

        $this->assertEquals(200, $response->getStatusCode(), $body);
        $this->assertStringNotContainsString(
            'EXCLUDED-BY-SCOPE',
            $body,
            'an eager-loaded relation was serialised without its resource scope'
        );
    }

    /**
     * Every eager-loaded path must resolve, segment by segment, to a
     * relationship the scoping code can find a resource for. A path it cannot
     * resolve is loaded unscoped, so this is the signal that a new relation
     * has slipped outside the mechanism.
     */
    #[Test]
    public function every_eager_loaded_relation_resolves_to_a_scopable_resource()
    {
        $api = $this->app()->getContainer()->make(JsonApi::class);

        $unresolved = [];

        foreach ($this->eagerLoadedPaths($api) as $type => $paths) {
            $resource = $api->resources[$type];

            foreach ($paths as $path) {
                $owner = $resource;

                foreach (explode('.', $path) as $segment) {
                    if (! $owner instanceof AbstractDatabaseResource) {
                        break;
                    }

                    $field = collect($this->fieldsOf($api, $owner))
                        ->first(fn ($field) => ($field instanceof ToOne || $field instanceof ToMany)
                            && (($field->property ?? $field->name) === $segment || $field->name === $segment));

                    if (! $field) {
                        // A segment that is not a relationship field is not
                        // serialised, so nothing is disclosed by loading it:
                        // `state` is read for isUnread and is already scoped to
                        // the actor by the relation itself. Only a serialisable
                        // relation can leak, and those must resolve.
                        break;
                    }

                    $types = (array) ($field->collections ?? []);

                    // A serialisable relation must resolve to at least one
                    // database resource, or scopeEagerLoads() has nothing to
                    // apply and it is loaded unscoped.
                    if ($this->isSerialisable($field)) {
                        $scopable = array_filter(
                            $types,
                            fn ($t) => ($api->resources[$t] ?? null) instanceof AbstractDatabaseResource
                        );

                        if (! $scopable) {
                            $unresolved[] = "$type: $path (at '$segment')";
                            break;
                        }
                    }

                    $owner = count($types) === 1 ? ($api->resources[$types[0]] ?? null) : null;
                }
            }
        }

        $this->assertSame(
            [],
            $unresolved,
            "These eager-loaded relations could not be resolved to a resource, so they are "
            ."loaded without a visibility scope:\n\n  ".implode("\n  ", $unresolved)."\n\n"
            ."Either expose the relationship on the resource, or drop it from the eager-load list."
        );
    }

    /**
     * @return array<object>
     */
    private function fieldsOf(JsonApi $api, AbstractDatabaseResource $resource): array
    {
        return $resource->fields();
    }

    /**
     * Whether a relationship can reach the client, and so can disclose the
     * records it is loaded with.
     */
    private function isSerialisable(ToOne|ToMany $field): bool
    {
        return ($field->includable ?? false) || ($field->visible ?? true) !== false;
    }
}
