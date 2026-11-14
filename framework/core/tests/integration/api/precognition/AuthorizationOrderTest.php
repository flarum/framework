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
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

/**
 * A precognitive request is refused before validation whenever the normal one
 * would be, so a 422 or 204 leaks nothing about what the actor can't reach.
 * Each case sends an invalid body.
 */
class AuthorizationOrderTest extends TestCase
{
    use RetrievesAuthorizedUsers;
    use PrecognitionTestHelpers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->prepareDatabase([
            User::class => [
                $this->normalUser(),
                ['id' => 3, 'username' => 'other', 'email' => 'other@machine.local', 'is_email_confirmed' => 1, 'password' => '$2y$10$LO59tiT7uggl6Oe23o/O6.utnF6ipngYjvMvaxo1TciKqBttDNKim'],
            ],
            Discussion::class => [
                ['id' => 1, 'title' => 'Public', 'user_id' => 3, 'first_post_id' => 1, 'comment_count' => 1, 'created_at' => Carbon::now()],
                ['id' => 2, 'title' => 'Private', 'user_id' => 3, 'first_post_id' => 2, 'comment_count' => 1, 'is_private' => 1, 'created_at' => Carbon::now()],
            ],
            Post::class => [
                ['id' => 1, 'discussion_id' => 1, 'user_id' => 3, 'type' => 'comment', 'content' => '<t><p>Hello</p></t>', 'number' => 1, 'created_at' => Carbon::now()],
                ['id' => 2, 'discussion_id' => 2, 'user_id' => 3, 'type' => 'comment', 'content' => '<t><p>Private</p></t>', 'number' => 1, 'is_private' => 1, 'created_at' => Carbon::now()],
            ],
        ]);
    }

    /**
     * [method, path, actor (null = guest), invalid body].
     */
    public static function refusedCases(): array
    {
        $tooLong = str_repeat('x', 300);

        return [
            'member renames another user' => ['PATCH', '/api/users/3', 2, ['data' => ['type' => 'users', 'id' => '3', 'attributes' => ['username' => 'bad name!']]]],
            'member changes admin groups' => ['PATCH', '/api/users/1', 2, ['data' => ['type' => 'users', 'id' => '1', 'relationships' => ['groups' => ['data' => [['type' => 'groups', 'id' => '999']]]]]]],
            'guest updates a user' => ['PATCH', '/api/users/2', null, ['data' => ['type' => 'users', 'id' => '2', 'attributes' => ['username' => 'bad name!']]]],
            'member renames private discussion' => ['PATCH', '/api/discussions/2', 2, ['data' => ['type' => 'discussions', 'id' => '2', 'attributes' => ['title' => '']]]],
            'member edits others post' => ['PATCH', '/api/posts/1', 2, ['data' => ['type' => 'posts', 'id' => '1', 'attributes' => ['content' => '']]]],
            'guest starts discussion' => ['POST', '/api/discussions', null, ['data' => ['type' => 'discussions', 'attributes' => ['title' => '', 'content' => '']]]],
            'member creates group' => ['POST', '/api/groups', 2, ['data' => ['type' => 'groups', 'attributes' => ['nameSingular' => $tooLong]]]],
            'member edits group' => ['PATCH', '/api/groups/1', 2, ['data' => ['type' => 'groups', 'id' => '1', 'attributes' => ['nameSingular' => $tooLong]]]],
            'guest creates access token' => ['POST', '/api/access-tokens', null, ['data' => ['type' => 'access-tokens', 'attributes' => ['title' => $tooLong]]]],
        ];
    }

    #[Test]
    #[DataProvider('refusedCases')]
    public function precognitive_request_is_refused_like_the_normal_request(string $method, string $path, ?int $as, array $body)
    {
        $normal = $this->send($this->jsonRequest($method, $path, $body, $as));
        $precognitive = $this->send($this->precognitive($this->jsonRequest($method, $path, $body, $as)));

        $this->assertContains($precognitive->getStatusCode(), [401, 403, 404], (string) $precognitive->getBody());
        $this->assertEquals($normal->getStatusCode(), $precognitive->getStatusCode(), "normal: {$normal->getBody()}\nprecognitive: {$precognitive->getBody()}");
    }
}
