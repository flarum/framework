<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\api\precognition;

require_once __DIR__.'/PrecognitionTestHelpers.php';

use Carbon\Carbon;
use Flarum\Discussion\Discussion;
use Flarum\Post\Post;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use Illuminate\Contracts\Events\Dispatcher;
use PHPUnit\Framework\Attributes\Test;

class SideEffectsTest extends TestCase
{
    use RetrievesAuthorizedUsers;
    use PrecognitionTestHelpers;

    protected const TABLES = [
        'users', 'groups', 'group_user', 'discussions', 'posts', 'access_tokens',
        'email_tokens', 'password_tokens', 'notifications', 'discussion_user',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->prepareDatabase([
            User::class => [
                $this->normalUser(),
            ],
            Discussion::class => [
                ['id' => 1, 'title' => 'Public', 'user_id' => 2, 'first_post_id' => 1, 'comment_count' => 1, 'created_at' => Carbon::now()],
            ],
            Post::class => [
                ['id' => 1, 'discussion_id' => 1, 'user_id' => 2, 'type' => 'comment', 'content' => '<t><p>Hello</p></t>', 'number' => 1, 'created_at' => Carbon::now()],
            ],
        ]);
    }

    /**
     * Valid precognitive creates and updates on core resources answer 204 and
     * change nothing: no rows, no domain or model write events.
     */
    #[Test]
    public function valid_precognitive_writes_change_nothing()
    {
        $requests = [
            ['POST', '/api/users', null, ['data' => ['type' => 'users', 'attributes' => ['username' => 'newcomer', 'email' => 'newcomer@machine.local', 'password' => 'long-enough-password']]]],
            ['PATCH', '/api/users/2', 2, ['data' => ['type' => 'users', 'id' => '2', 'attributes' => ['preferences' => ['followAfterReply' => true]]]]],
            ['PATCH', '/api/users/2', 1, ['data' => ['type' => 'users', 'id' => '2', 'attributes' => ['email' => 'changed@machine.local', 'isEmailConfirmed' => false], 'relationships' => ['groups' => ['data' => [['type' => 'groups', 'id' => '4']]]]]]],
            ['POST', '/api/groups', 1, ['data' => ['type' => 'groups', 'attributes' => ['nameSingular' => 'precog', 'namePlural' => 'precogs']]]],
            ['PATCH', '/api/groups/4', 1, ['data' => ['type' => 'groups', 'id' => '4', 'attributes' => ['nameSingular' => 'Mod', 'namePlural' => 'Mods', 'isHidden' => true]]]],
            ['POST', '/api/discussions', 2, ['data' => ['type' => 'discussions', 'attributes' => ['title' => 'A new discussion', 'content' => 'Some content']]]],
            ['PATCH', '/api/discussions/1', 2, ['data' => ['type' => 'discussions', 'id' => '1', 'attributes' => ['title' => 'Renamed']]]],
            ['POST', '/api/posts', 2, ['data' => ['type' => 'posts', 'attributes' => ['content' => 'A reply'], 'relationships' => ['discussion' => ['data' => ['type' => 'discussions', 'id' => '1']]]]]],
            ['PATCH', '/api/posts/1', 2, ['data' => ['type' => 'posts', 'id' => '1', 'attributes' => ['content' => 'Edited']]]],
            ['PATCH', '/api/posts/1', 1, ['data' => ['type' => 'posts', 'id' => '1', 'attributes' => ['isHidden' => true]]]],
            ['POST', '/api/access-tokens', 1, ['data' => ['type' => 'access-tokens', 'attributes' => ['title' => 'Precog']]]],
        ];

        // Build every request first: authenticating as a user creates an access token.
        $requests = array_map(fn (array $r) => $this->precognitive($this->jsonRequest($r[0], $r[1], $r[3], $r[2])), $requests);

        $before = $this->snapshot();

        $events = [];
        $this->app()->getContainer()->make(Dispatcher::class)->listen('*', function (string $name) use (&$events) {
            $events[] = $name;
        });

        foreach ($requests as $request) {
            $response = $this->send($request);

            $this->assertEquals(204, $response->getStatusCode(), $request->getMethod().' '.$request->getUri()->getPath().': '.$response->getBody());
        }

        $this->assertEquals($before, $this->snapshot());

        // Authenticating a request touches the access token and the user's last_seen_at
        // (both model saves); the snapshot above covers the user columns that matter.
        $writes = array_values(array_unique(array_filter($events, fn (string $name) => (
            (bool) preg_match('/^eloquent\.(creat|sav|updat|delet|restor)/', $name)
            && ! str_contains($name, 'AccessToken') && ! str_ends_with($name, 'Flarum\User\User')
        ) || (str_starts_with($name, 'Flarum\\') && str_contains($name, '\\Event\\')))));

        $this->assertSame([], $writes);
    }

    /**
     * @return array<string, mixed>
     */
    protected function snapshot(): array
    {
        $snapshot = [];

        foreach (static::TABLES as $table) {
            $snapshot[$table] = $this->database()->table($table)->count();
        }

        $snapshot['user 2'] = (array) $this->database()->table('users')->where('id', 2)->first(['username', 'email', 'is_email_confirmed', 'preferences']);
        $snapshot['group 4'] = (array) $this->database()->table('groups')->where('id', 4)->first(['name_singular', 'is_hidden']);
        $snapshot['discussion 1'] = (array) $this->database()->table('discussions')->where('id', 1)->first(['title']);
        $snapshot['post 1'] = (array) $this->database()->table('posts')->where('id', 1)->first(['content', 'hidden_at']);

        return $snapshot;
    }
}
