<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\security\precognition;

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
 * A precognitive request must be refused (401/403/404) before validation runs
 * whenever the same normal request would be refused: otherwise the 422-vs-204
 * answer leaks information about records or endpoints the actor can't reach.
 * Each case sends a body that would be *invalid* (so validation would 422)
 * and asserts the precognitive answer equals the normal answer's status.
 */
class AuthorizationOrderTest extends TestCase
{
    use RetrievesAuthorizedUsers;
    use PrecognitionTestHelpers;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['flarum-tags', 'flarum-flags', 'flarum-gdpr', 'flarum-messages', 'flarum-suspend', 'flarum-likes', 'flarum-oauth-provider'] as $extension) {
            $this->extension($extension);
        }

        $this->prepareDatabase([
            User::class => [
                $this->normalUser(),
                ['id' => 3, 'username' => 'other', 'email' => 'other@machine.local', 'is_email_confirmed' => 1, 'password' => '$2y$10$LO59tiT7uggl6Oe23o/O6.utnF6ipngYjvMvaxo1TciKqBttDNKim'],
                ['id' => 4, 'username' => 'suspended', 'email' => 'suspended@machine.local', 'is_email_confirmed' => 1, 'password' => '$2y$10$LO59tiT7uggl6Oe23o/O6.utnF6ipngYjvMvaxo1TciKqBttDNKim', 'suspended_until' => Carbon::now()->addDay()],
            ],
            Discussion::class => [
                ['id' => 1, 'title' => 'Public', 'user_id' => 3, 'first_post_id' => 1, 'comment_count' => 2, 'created_at' => Carbon::now()],
                ['id' => 2, 'title' => 'Private', 'user_id' => 3, 'first_post_id' => 3, 'comment_count' => 1, 'is_private' => 1, 'created_at' => Carbon::now()],
                ['id' => 3, 'title' => 'Hidden', 'user_id' => 3, 'first_post_id' => 4, 'comment_count' => 1, 'hidden_at' => Carbon::now(), 'created_at' => Carbon::now()],
            ],
            Post::class => [
                ['id' => 1, 'discussion_id' => 1, 'user_id' => 3, 'type' => 'comment', 'content' => '<t><p>Hello</p></t>', 'number' => 1, 'created_at' => Carbon::now()],
                ['id' => 2, 'discussion_id' => 1, 'user_id' => 3, 'type' => 'comment', 'content' => '<t><p>Hidden</p></t>', 'number' => 2, 'hidden_at' => Carbon::now(), 'created_at' => Carbon::now()],
                ['id' => 3, 'discussion_id' => 2, 'user_id' => 3, 'type' => 'comment', 'content' => '<t><p>Private</p></t>', 'number' => 1, 'is_private' => 1, 'created_at' => Carbon::now()],
                ['id' => 4, 'discussion_id' => 3, 'user_id' => 3, 'type' => 'comment', 'content' => '<t><p>In hidden</p></t>', 'number' => 1, 'created_at' => Carbon::now()],
            ],
            'tags' => [
                ['id' => 1, 'name' => 'General', 'slug' => 'general', 'position' => 0],
                ['id' => 2, 'name' => 'Staff', 'slug' => 'staff', 'position' => 1, 'is_restricted' => 1],
            ],
            'group_permission' => [
                ['permission' => 'tag2.viewForum', 'group_id' => 4],
            ],
            'dialogs' => [
                ['id' => 1, 'type' => 'direct'],
            ],
            'dialog_messages' => [
                ['id' => 1, 'dialog_id' => 1, 'user_id' => 1, 'content' => '<t><p>Hi</p></t>', 'number' => 1, 'created_at' => Carbon::now()],
            ],
            'dialog_user' => [
                ['dialog_id' => 1, 'user_id' => 1, 'joined_at' => Carbon::now()],
                ['dialog_id' => 1, 'user_id' => 3, 'joined_at' => Carbon::now()],
            ],
            'gdpr_erasure' => [
                ['id' => 1, 'user_id' => 3, 'verification_token' => 'abc123', 'status' => 'user_confirmed', 'reason' => 'bye', 'created_at' => Carbon::now(), 'user_confirmed_at' => Carbon::now()],
            ],
        ]);
    }

    /**
     * [label, method, path, actor (null = guest), invalid body].
     */
    public static function refusedCases(): array
    {
        $tooLong = str_repeat('x', 300);

        return [
            // Another user's credentials: member can't edit credentials of user 3.
            'member renames another user' => ['PATCH', '/api/users/3', 2, ['data' => ['type' => 'users', 'id' => '3', 'attributes' => ['username' => 'bad name!']]]],
            'member changes another user email' => ['PATCH', '/api/users/3', 2, ['data' => ['type' => 'users', 'id' => '3', 'attributes' => ['email' => 'not-an-email']]]],
            'member sets another user password' => ['PATCH', '/api/users/3', 2, ['data' => ['type' => 'users', 'id' => '3', 'attributes' => ['password' => 'x']]]],
            'member changes admin groups' => ['PATCH', '/api/users/1', 2, ['data' => ['type' => 'users', 'id' => '1', 'relationships' => ['groups' => ['data' => [['type' => 'groups', 'id' => '999']]]]]]],
            'guest updates a user' => ['PATCH', '/api/users/2', null, ['data' => ['type' => 'users', 'id' => '2', 'attributes' => ['username' => 'bad name!']]]],
            // Discussions and posts the actor can't see or edit.
            'member renames private discussion' => ['PATCH', '/api/discussions/2', 2, ['data' => ['type' => 'discussions', 'id' => '2', 'attributes' => ['title' => '']]]],
            'member renames hidden discussion' => ['PATCH', '/api/discussions/3', 2, ['data' => ['type' => 'discussions', 'id' => '3', 'attributes' => ['title' => '']]]],
            'member renames others discussion' => ['PATCH', '/api/discussions/1', 2, ['data' => ['type' => 'discussions', 'id' => '1', 'attributes' => ['title' => '']]]],
            'member edits hidden post' => ['PATCH', '/api/posts/2', 2, ['data' => ['type' => 'posts', 'id' => '2', 'attributes' => ['content' => '']]]],
            'member edits private post' => ['PATCH', '/api/posts/3', 2, ['data' => ['type' => 'posts', 'id' => '3', 'attributes' => ['content' => '']]]],
            'member edits others post' => ['PATCH', '/api/posts/1', 2, ['data' => ['type' => 'posts', 'id' => '1', 'attributes' => ['content' => '']]]],
            'member replies to private discussion' => ['POST', '/api/posts', 2, ['data' => ['type' => 'posts', 'attributes' => ['content' => ''], 'relationships' => ['discussion' => ['data' => ['type' => 'discussions', 'id' => '2']]]]]],
            'guest replies' => ['POST', '/api/posts', null, ['data' => ['type' => 'posts', 'attributes' => ['content' => ''], 'relationships' => ['discussion' => ['data' => ['type' => 'discussions', 'id' => '1']]]]]],
            'guest starts discussion' => ['POST', '/api/discussions', null, ['data' => ['type' => 'discussions', 'attributes' => ['title' => '', 'content' => '']]]],
            'suspended starts discussion' => ['POST', '/api/discussions', 4, ['data' => ['type' => 'discussions', 'attributes' => ['title' => '', 'content' => '']]]],
            'suspended replies' => ['POST', '/api/posts', 4, ['data' => ['type' => 'posts', 'attributes' => ['content' => ''], 'relationships' => ['discussion' => ['data' => ['type' => 'discussions', 'id' => '1']]]]]],
            'suspended renames self' => ['PATCH', '/api/users/4', 4, ['data' => ['type' => 'users', 'id' => '4', 'attributes' => ['username' => 'bad name!']]]],
            // Admin-only resources.
            'member creates group' => ['POST', '/api/groups', 2, ['data' => ['type' => 'groups', 'attributes' => ['nameSingular' => $tooLong]]]],
            'member edits group' => ['PATCH', '/api/groups/1', 2, ['data' => ['type' => 'groups', 'id' => '1', 'attributes' => ['nameSingular' => $tooLong]]]],
            'member creates access token' => ['POST', '/api/access-tokens', 2, ['data' => ['type' => 'access-tokens', 'attributes' => ['title' => $tooLong]]]],
            'guest creates access token' => ['POST', '/api/access-tokens', null, ['data' => ['type' => 'access-tokens', 'attributes' => ['title' => $tooLong]]]],
            'member creates tag' => ['POST', '/api/tags', 2, ['data' => ['type' => 'tags', 'attributes' => ['name' => '', 'slug' => '']]]],
            'member edits tag' => ['PATCH', '/api/tags/1', 2, ['data' => ['type' => 'tags', 'id' => '1', 'attributes' => ['name' => '']]]],
            'member edits restricted tag' => ['PATCH', '/api/tags/2', 2, ['data' => ['type' => 'tags', 'id' => '2', 'attributes' => ['name' => '']]]],
            'member creates oauth client' => ['POST', '/api/oauth-provider-clients', 2, ['data' => ['type' => 'oauth-provider-clients', 'attributes' => ['name' => '']]]],
            // Extension resources.
            'guest flags a post' => ['POST', '/api/flags', null, ['data' => ['type' => 'flags', 'attributes' => [], 'relationships' => ['post' => ['data' => ['type' => 'posts', 'id' => '1']]]]]],
            'member flags a hidden post' => ['POST', '/api/flags', 2, ['data' => ['type' => 'flags', 'attributes' => [], 'relationships' => ['post' => ['data' => ['type' => 'posts', 'id' => '2']]]]]],
            'member processes erasure' => ['PATCH', '/api/user-erasure-requests/1', 2, ['data' => ['type' => 'user-erasure-requests', 'id' => '1', 'attributes' => ['processedMode' => 'nope']]]],
            'guest requests erasure' => ['POST', '/api/user-erasure-requests', null, ['data' => ['type' => 'user-erasure-requests', 'attributes' => ['reason' => $tooLong]]]],
            'member messages dialog they are not in' => ['POST', '/api/dialog-messages', 2, ['data' => ['type' => 'dialog-messages', 'attributes' => ['content' => ''], 'relationships' => ['dialog' => ['data' => ['type' => 'dialogs', 'id' => '1']]]]]],
            'member updates dialog they are not in' => ['PATCH', '/api/dialogs/1', 2, ['data' => ['type' => 'dialogs', 'id' => '1', 'attributes' => ['name' => $tooLong]]]],
            'member likes hidden post' => ['PATCH', '/api/posts/2', 2, ['data' => ['type' => 'posts', 'id' => '2', 'attributes' => ['isLiked' => 'not-a-bool']]]],
            'suspended likes post' => ['PATCH', '/api/posts/1', 4, ['data' => ['type' => 'posts', 'id' => '1', 'attributes' => ['isLiked' => 'not-a-bool']]]],
            'member suspends admin' => ['PATCH', '/api/users/1', 2, ['data' => ['type' => 'users', 'id' => '1', 'attributes' => ['suspendedUntil' => 'not-a-date']]]],
        ];
    }

    #[Test]
    #[DataProvider('refusedCases')]
    public function precognitive_request_is_refused_like_the_normal_request(string $method, string $path, ?int $as, array $body)
    {
        $this->unlimitedPrecognition();

        $normal = $this->send($this->jsonRequest($method, $path, $body, $as));
        $precognitive = $this->send($this->precognitive($this->jsonRequest($method, $path, $body, $as)));

        $this->assertNotEquals(204, $precognitive->getStatusCode(), (string) $precognitive->getBody());
        $this->assertNotEquals(422, $precognitive->getStatusCode(), 'validation reached: '.$precognitive->getBody());
        $this->assertEquals($normal->getStatusCode(), $precognitive->getStatusCode(), "normal: {$normal->getBody()}\nprecognitive: {$precognitive->getBody()}");
    }

    /**
     * Relationships to hidden records must answer like relationships to
     * missing records, so `exists`-style checks can't reveal hidden ids.
     */
    #[Test]
    public function relationship_to_hidden_record_is_indistinguishable_from_missing()
    {
        $this->unlimitedPrecognition();

        $start = fn (string $tagId) => $this->precognitive($this->jsonRequest('POST', '/api/discussions', ['data' => [
            'type' => 'discussions',
            'attributes' => ['title' => 'A title', 'content' => 'Some content'],
            'relationships' => ['tags' => ['data' => [['type' => 'tags', 'id' => $tagId]]]],
        ]], 2));

        $hidden = $this->send($start('2'));
        $missing = $this->send($start('999'));

        $this->assertEquals($missing->getStatusCode(), $hidden->getStatusCode(), "hidden: {$hidden->getBody()}\nmissing: {$missing->getBody()}");
        // Tests run in debug mode, so `detail` holds a stack trace: compare the error codes.
        $this->assertEquals(
            array_column($this->body($missing)['errors'] ?? [], 'code'),
            array_column($this->body($hidden)['errors'] ?? [], 'code')
        );

        $flag = fn (string $postId) => $this->precognitive($this->jsonRequest('POST', '/api/flags', ['data' => [
            'type' => 'flags',
            'attributes' => ['reason' => 'spam'],
            'relationships' => ['post' => ['data' => ['type' => 'posts', 'id' => $postId]]],
        ]], 2));

        $hidden = $this->send($flag('3'));
        $missing = $this->send($flag('999'));

        $this->assertEquals($missing->getStatusCode(), $hidden->getStatusCode(), "hidden: {$hidden->getBody()}\nmissing: {$missing->getBody()}");

        $recipient = fn (string $userId) => $this->precognitive($this->jsonRequest('POST', '/api/dialog-messages', ['data' => [
            'type' => 'dialog-messages',
            'attributes' => ['content' => 'hi', 'users' => [['id' => $userId]]],
        ]], 2));

        $this->assertEquals(
            $this->send($recipient('999'))->getStatusCode(),
            $this->send($recipient('3'))->getStatusCode()
        );
    }
}
