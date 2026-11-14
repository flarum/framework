<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\api\users;

use Carbon\Carbon;
use Flarum\Api\Resource\UserResource;
use Flarum\Api\Schema;
use Flarum\Discussion\Discussion;
use Flarum\Extend;
use Flarum\Group\Group;
use Flarum\Post\Post;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\Test;

/**
 * A user's groups decide whether they are an admin and what they may do, and
 * the policies that decide what is serialized about a user read them from the
 * same relation the serializer does. That relation has to hold every group the
 * user is in, hidden ones included, whoever is looking: only what is
 * serialized leaves the hidden groups out.
 *
 * Here the admin group is hidden, as it is on many forums, and none of the
 * actors but the admin can see hidden groups.
 */
class HiddenGroupPermissionsTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    /** May edit users' credentials, but not an admin's. */
    private const EDITOR = 3;

    /** In a hidden group, and has a permission only that group gives. */
    private const INSIDER = 4;

    private const HIDDEN_GROUP = 11;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extend(
            (new Extend\ApiResource(UserResource::class))
                ->fields(fn () => [
                    // Like the flags extensions serialize about a user, such as
                    // canMentionGroups and canSendAnyMessage: a permission of the
                    // user being serialized, not of the actor.
                    Schema\Boolean::make('hasInsiderPermission')
                        ->get(fn (User $user) => $user->hasPermission('insiderThing')),
                ])
        );

        $this->prepareDatabase([
            User::class => [
                $this->normalUser(),
                ['id' => self::EDITOR, 'username' => 'editor', 'email' => 'editor@machine.local', 'is_email_confirmed' => 1],
                ['id' => self::INSIDER, 'username' => 'insider', 'email' => 'insider@machine.local', 'is_email_confirmed' => 1],
            ],
            Group::class => [
                ['id' => Group::ADMINISTRATOR_ID, 'name_singular' => 'Admin', 'name_plural' => 'Admins', 'is_hidden' => 1],
                ['id' => 10, 'name_singular' => 'Editor', 'name_plural' => 'Editors', 'is_hidden' => 0],
                ['id' => self::HIDDEN_GROUP, 'name_singular' => 'Insider', 'name_plural' => 'Insiders', 'is_hidden' => 1],
            ],
            'group_user' => [
                ['user_id' => self::EDITOR, 'group_id' => 10],
                ['user_id' => self::INSIDER, 'group_id' => self::HIDDEN_GROUP],
            ],
            'group_permission' => [
                ['permission' => 'user.editCredentials', 'group_id' => 10],
                ['permission' => 'insiderThing', 'group_id' => self::HIDDEN_GROUP],
            ],
            Discussion::class => [
                ['id' => 1, 'title' => 'Started by the admin', 'created_at' => Carbon::now(), 'last_posted_at' => Carbon::now(), 'user_id' => 1, 'first_post_id' => 1, 'last_posted_user_id' => self::INSIDER, 'comment_count' => 3],
            ],
            Post::class => [
                ['id' => 1, 'number' => 1, 'discussion_id' => 1, 'created_at' => Carbon::now(), 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p>From the admin</p></t>'],
                // The admin and the insider also edited a post each, after the
                // posts they wrote, so the serializer loads them as editors
                // through its own relationship loading as well as through the
                // endpoints' eager loads.
                ['id' => 2, 'number' => 2, 'discussion_id' => 1, 'created_at' => Carbon::now(), 'user_id' => self::INSIDER, 'type' => 'comment', 'content' => '<t><p>From the insider</p></t>', 'edited_at' => Carbon::now(), 'edited_user_id' => 1],
                ['id' => 3, 'number' => 3, 'discussion_id' => 1, 'created_at' => Carbon::now(), 'user_id' => 2, 'type' => 'comment', 'content' => '<t><p>From a member</p></t>', 'edited_at' => Carbon::now(), 'edited_user_id' => self::INSIDER],
            ],
        ]);
    }

    /**
     * The requests that serialize the admin and the insider with their groups
     * eager loaded by the endpoint.
     *
     * @return array<string, array{string, array}>
     */
    private static function eagerLoadingRequests(int $user): array
    {
        return [
            'user' => ["/api/users/$user", []],
            'user list' => ['/api/users', []],
            'discussion' => ['/api/discussions/1', []],
            'discussion list' => ['/api/discussions', []],
            'post' => ['/api/posts/'.($user === 1 ? 1 : 2), []],
            'post list' => ['/api/posts', ['filter' => ['discussion' => '1']]],
        ];
    }

    private function get(int $actor, string $path, array $query = []): array
    {
        $response = $this->send(
            $this->request('GET', $path, ['authenticatedAs' => $actor])->withQueryParams($query)
        );

        $body = (string) $response->getBody();

        $this->assertEquals(200, $response->getStatusCode(), $body);

        return json_decode($body, true);
    }

    /**
     * Every user in a response, primary or included, keyed by id.
     *
     * @return array<int, array>
     */
    private function users(array $body): array
    {
        $resources = isset($body['data']['type']) ? [$body['data']] : $body['data'];

        $users = [];

        foreach ([...$resources, ...$body['included'] ?? []] as $resource) {
            if ($resource['type'] === 'users') {
                $users[(int) $resource['id']] = $resource;
            }
        }

        return $users;
    }

    #[Test]
    public function an_admin_in_a_hidden_group_is_still_treated_as_an_admin(): void
    {
        $admin = $this->users($this->get(self::EDITOR, '/api/users/1'))[1];

        $this->assertFalse($admin['attributes']['canEditCredentials'], 'Only an admin may edit an admin\'s credentials.');
        $this->assertArrayNotHasKey('email', $admin['attributes']);
        $this->assertArrayNotHasKey('isEmailConfirmed', $admin['attributes']);
    }

    #[Test]
    public function an_admins_email_is_not_disclosed_where_their_groups_are_eager_loaded(): void
    {
        foreach (self::eagerLoadingRequests(1) as $name => [$path, $query]) {
            $body = $this->get(self::EDITOR, $path, $query);

            $this->assertArrayHasKey(1, $this->users($body), "The $name request should serialize the admin.");
            $this->assertStringNotContainsString('admin@machine.local', json_encode($body), "The $name request disclosed the admin's email.");
        }
    }

    #[Test]
    public function an_admins_email_is_not_disclosed_where_the_serializer_loads_them(): void
    {
        $body = $this->get(self::EDITOR, '/api/posts', ['filter' => ['discussion' => '1'], 'include' => 'editedUser']);

        $this->assertArrayHasKey(1, $this->users($body));
        $this->assertStringNotContainsString('admin@machine.local', json_encode($body));
    }

    #[Test]
    public function a_permission_from_a_hidden_group_counts_where_the_users_groups_are_eager_loaded(): void
    {
        foreach (self::eagerLoadingRequests(self::INSIDER) as $name => [$path, $query]) {
            $insider = $this->users($this->get(2, $path, $query))[self::INSIDER] ?? null;

            $this->assertNotNull($insider, "The $name request should serialize the insider.");
            $this->assertTrue($insider['attributes']['hasInsiderPermission'], "The $name request left out the permission the insider has from a hidden group.");
        }
    }

    #[Test]
    public function a_permission_from_a_hidden_group_counts_where_the_serializer_loads_the_user(): void
    {
        $body = $this->get(2, '/api/posts', ['filter' => ['discussion' => '1'], 'include' => 'editedUser']);

        $this->assertTrue($this->users($body)[self::INSIDER]['attributes']['hasInsiderPermission']);
    }

    #[Test]
    public function hidden_groups_are_still_left_out_of_what_is_serialized(): void
    {
        $hidden = [(string) Group::ADMINISTRATOR_ID, (string) self::HIDDEN_GROUP];

        $requests = [];

        foreach ([...array_values(self::eagerLoadingRequests(1)), ...array_values(self::eagerLoadingRequests(self::INSIDER))] as [$path, $query]) {
            $requests[$path.'?'.http_build_query($query)] = [$path, $query];
        }

        foreach ([2, self::EDITOR] as $actor) {
            foreach ($requests as $request => [$path, $query]) {
                $body = $this->get($actor, $path, $query);

                $included = array_column(array_filter($body['included'] ?? [], fn (array $resource) => $resource['type'] === 'groups'), 'id');

                $this->assertEmpty(array_intersect($hidden, $included), "$request included a hidden group for user $actor.");

                foreach ($this->users($body) as $id => $user) {
                    $linked = array_column($user['relationships']['groups']['data'] ?? [], 'id');

                    $this->assertEmpty(array_intersect($hidden, $linked), "$request linked user $id to a hidden group for user $actor.");
                }
            }
        }
    }

    #[Test]
    public function those_who_can_see_hidden_groups_still_see_them(): void
    {
        $insider = $this->users($this->get(1, '/api/users/'.self::INSIDER))[self::INSIDER];

        $this->assertEquals([(string) self::HIDDEN_GROUP], array_column($insider['relationships']['groups']['data'], 'id'));
    }
}
