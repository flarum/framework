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
use Flarum\Api\Endpoint\Create;
use Flarum\Api\Endpoint\Update;
use Flarum\Api\JsonApi;
use Flarum\Api\Resource\AbstractDatabaseResource;
use Flarum\Api\Resource\AbstractResource;
use Flarum\Api\Schema;
use Flarum\Discussion\Discussion;
use Flarum\Post\Post;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use Illuminate\Contracts\Events\Dispatcher;
use PHPUnit\Framework\Attributes\Test;
use Tobyz\JsonApiServer\Schema\Field\Field;
use Tobyz\JsonApiServer\Schema\Field\Relationship;
use Tobyz\JsonApiServer\Schema\Field\ToMany;

/**
 * Sends a precognitive request to every Create and Update endpoint of every
 * registered resource (core + the bundled extensions enabled below), once with
 * every attribute/relationship filled and once per field, as an admin and as a
 * member, and asserts nothing observable changed: no table content, no file,
 * no domain/eloquent write event, no mail.
 */
class SideEffectSweepTest extends TestCase
{
    use RetrievesAuthorizedUsers;
    use PrecognitionTestHelpers;

    protected const EXTENSIONS = [
        'flarum-tags', 'flarum-flags', 'flarum-approval', 'flarum-gdpr', 'flarum-messages',
        'flarum-likes', 'flarum-suspend', 'flarum-lock', 'flarum-sticky', 'flarum-subscriptions',
        'flarum-mentions', 'flarum-nicknames', 'flarum-audit', 'flarum-oauth-provider',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        foreach (static::EXTENSIONS as $extension) {
            $this->extension($extension);
        }

        $this->prepareDatabase([
            User::class => [
                $this->normalUser(),
                ['id' => 3, 'username' => 'other', 'email' => 'other@machine.local', 'is_email_confirmed' => 1, 'password' => '$2y$10$LO59tiT7uggl6Oe23o/O6.utnF6ipngYjvMvaxo1TciKqBttDNKim'],
            ],
            Discussion::class => [
                ['id' => 1, 'title' => 'Public', 'user_id' => 3, 'first_post_id' => 1, 'comment_count' => 1, 'created_at' => Carbon::now()],
            ],
            Post::class => [
                ['id' => 1, 'discussion_id' => 1, 'user_id' => 3, 'type' => 'comment', 'content' => '<t><p>Hello</p></t>', 'number' => 1, 'created_at' => Carbon::now()],
            ],
            'tags' => [
                ['id' => 1, 'name' => 'General', 'slug' => 'general', 'position' => 0],
            ],
            'flags' => [
                ['id' => 1, 'post_id' => 1, 'user_id' => 3, 'type' => 'user', 'reason' => 'spam', 'created_at' => Carbon::now()],
            ],
            'dialogs' => [
                ['id' => 1, 'type' => 'direct'],
            ],
            'dialog_messages' => [
                ['id' => 1, 'dialog_id' => 1, 'user_id' => 2, 'content' => '<t><p>Hi</p></t>', 'number' => 1, 'created_at' => Carbon::now()],
            ],
            'dialog_user' => [
                ['dialog_id' => 1, 'user_id' => 2, 'joined_at' => Carbon::now()],
                ['dialog_id' => 1, 'user_id' => 3, 'joined_at' => Carbon::now()],
            ],
            'gdpr_erasure' => [
                ['id' => 1, 'user_id' => 3, 'verification_token' => 'abc123', 'status' => 'user_confirmed', 'reason' => 'bye', 'created_at' => Carbon::now(), 'user_confirmed_at' => Carbon::now()],
            ],
            'notifications' => [
                ['id' => 1, 'user_id' => 2, 'from_user_id' => 3, 'type' => 'discussionRenamed', 'subject_id' => 1, 'data' => '{}', 'created_at' => Carbon::now(), 'is_deleted' => 0],
            ],
        ]);
    }

    /**
     * @return array<int, array{AbstractResource, Create|Update}>
     */
    protected function savingEndpoints(): array
    {
        $container = $this->app()->getContainer();
        $api = $container->make(JsonApi::class);

        $found = [];

        foreach ($container->make('flarum.api.resources') as $class) {
            $type = $container->make($class)->type();
            $resource = $api->getResource($type);

            if (! $resource instanceof AbstractResource) {
                continue;
            }

            foreach ($resource->resolveEndpoints() as $endpoint) {
                if ($endpoint instanceof Create || $endpoint instanceof Update) {
                    $found[] = [$resource, $endpoint];
                }
            }
        }

        return $found;
    }

    protected function plausibleValue(Field $field): mixed
    {
        if ($field instanceof Relationship) {
            $type = $field->collections[0] ?? null;
            $id = $type ? $this->existingId($type) : null;

            if ($field instanceof ToMany) {
                return ['data' => $id ? [['type' => $type, 'id' => $id]] : []];
            }

            return ['data' => $id ? ['type' => $type, 'id' => $id] : null];
        }

        return match (true) {
            $field instanceof Schema\Boolean => true,
            $field instanceof Schema\Integer, $field instanceof Schema\Number => 1,
            $field instanceof Schema\Arr => [['id' => 3]],
            $field instanceof Schema\DateTime, $field instanceof Schema\Date => Carbon::now()->toIso8601String(),
            $field->name === 'email' => 'precog@machine.local',
            $field->name === 'password' => 'precog-password',
            $field->name === 'content' => 'Precognitive content',
            default => 'precog'.substr(md5($field->name), 0, 6),
        };
    }

    protected function existingId(string $type): ?string
    {
        try {
            $resource = $this->app()->getContainer()->make(JsonApi::class)->getResource($type);
        } catch (\Throwable) {
            return null;
        }

        if (! $resource instanceof AbstractDatabaseResource) {
            return null;
        }

        $class = $resource->model();
        $model = (new $class)->newQuery()->orderBy((new $class)->getKeyName())->first();

        return $model ? (string) $model->getKey() : null;
    }

    /**
     * @return array<int, array{string, array}>
     */
    protected function bodies(AbstractResource $resource, ?string $id): array
    {
        $attributes = [];
        $relationships = [];

        foreach ($resource->resolveFields() as $field) {
            if ($field instanceof Relationship) {
                $relationships[$field->name] = $this->plausibleValue($field);
            } else {
                $attributes[$field->name] = $this->plausibleValue($field);
            }
        }

        $data = fn (array $a, array $r) => ['data' => array_filter([
            'type' => $resource->type(),
            'id' => $id,
            'attributes' => $a ?: null,
            'relationships' => $r ?: null,
        ], fn ($v) => $v !== null), 'meta' => ['password' => 'too-obscure']];

        $bodies = [['all', $data($attributes, $relationships)], ['none', $data([], [])]];

        foreach ($attributes as $name => $value) {
            $bodies[] = ["attr:$name", $data([$name => $value], [])];
        }

        foreach ($relationships as $name => $value) {
            $bodies[] = ["rel:$name", $data([], [$name => $value])];
        }

        return $bodies;
    }

    #[Test]
    public function no_saving_endpoint_has_side_effects_when_precognitive()
    {
        $this->unlimitedPrecognition();
        $this->app();

        $events = [];
        $this->app()->getContainer()->make(Dispatcher::class)->listen('*', function (string $name) use (&$events) {
            $events[] = $name;
        });

        $statuses = [];
        $failures = [];

        foreach ($this->savingEndpoints() as [$resource, $endpoint]) {
            $type = $resource->type();
            $id = $endpoint instanceof Update ? $this->existingId($type) : null;

            if ($endpoint instanceof Update && $id === null) {
                $statuses["$type.update"] = 'skipped (no record)';
                continue;
            }

            $method = $endpoint instanceof Create ? 'POST' : 'PATCH';
            $path = '/api/'.$type.($id !== null ? '/'.$id : '');

            foreach ([1, 2, null] as $as) {
                foreach ($this->bodies($resource, $id) as [$label, $json]) {
                    if ($as !== 1 && ! in_array($label, ['all', 'none'], true)) {
                        continue;
                    }

                    $request = $this->precognitive($this->jsonRequest($method, $path, $json, $as));

                    $db = $this->databaseFingerprint();
                    $files = $this->fileFingerprint();
                    $events = [];

                    $response = $this->send($request);
                    $status = $response->getStatusCode();

                    $key = "$method $path as ".($as ?? 'guest')." [$label]";
                    $statuses[$key] = $status;

                    if ($dump = getenv('PRECOG_DUMP')) {
                        file_put_contents($dump, "$key => $status\n", FILE_APPEND);
                    }

                    if ($status >= 500) {
                        $failures[] = "$key: HTTP $status ".substr((string) $response->getBody(), 0, 300);
                    }

                    if ($response->getHeaderLine('Precognition') !== 'true') {
                        $failures[] = "$key: response not marked precognitive";
                    }

                    $diff = array_keys(array_diff_assoc($this->databaseFingerprint(), $db));
                    if ($diff) {
                        $failures[] = "$key: HTTP $status changed tables ".implode(', ', $diff);
                    }

                    if ($this->fileFingerprint() !== $files) {
                        $failures[] = "$key: changed files";
                    }

                    $writes = array_filter($events, fn (string $e) => (bool) preg_match('/^eloquent\.(creat|sav|updat|delet|restor)/', $e) && ! str_contains($e, 'AccessToken') && ! str_ends_with($e, 'Flarum\User\User')
                        || (bool) preg_match('/\\\\(Event|Events)\\\\(Saving|Saved|Created|Creating|Updated|Deleted|Deleting|Posted|Started|Registered|Renamed|Hidden|Restored|Revised|Flagged|Liked|Unliked|Sent|MessageSending|MessageSent|Activated|EmailChangeRequested|PasswordChanged|AvatarChanged|GroupsChanged|Suspended|Unsuspended)/', $e));

                    if ($writes) {
                        $failures[] = "$key: HTTP $status dispatched ".implode(', ', array_unique($writes));
                    }
                }
            }
        }

        if ($dump = getenv('PRECOG_DUMP')) {
            file_put_contents($dump, json_encode(array_count_values(array_map('strval', $statuses)), JSON_PRETTY_PRINT)."\n", FILE_APPEND);
        }

        $this->assertGreaterThan(15, count($statuses), 'too few endpoints swept');
        $this->assertSame([], $failures, implode("\n", $failures));
    }

    /**
     * Hand-written *valid* bodies, so validation passes (204) and every step the
     * precognitive path runs (newModel, defaults, deserialization, validation)
     * runs to completion. Then the same body sent normally must write, proving
     * the detectors see writes.
     */
    #[Test]
    public function valid_precognitive_creates_and_updates_write_nothing()
    {
        $this->unlimitedPrecognition();

        $events = [];
        $this->app()->getContainer()->make(Dispatcher::class)->listen('*', function (string $name) use (&$events) {
            $events[] = $name;
        });

        $cases = [
            ['POST', '/api/users', null, ['data' => ['type' => 'users', 'attributes' => ['username' => 'newbie', 'email' => 'newbie@machine.local', 'password' => 'long-enough-pw']]]],
            ['POST', '/api/users', 1, ['data' => ['type' => 'users', 'attributes' => ['username' => 'newbie', 'email' => 'newbie@machine.local', 'password' => 'long-enough-pw', 'isEmailConfirmed' => true]]]],
            ['POST', '/api/discussions', 2, ['data' => ['type' => 'discussions', 'attributes' => ['title' => 'Precog title', 'content' => 'Precog content'], 'relationships' => ['tags' => ['data' => [['type' => 'tags', 'id' => '1']]]]]]],
            ['POST', '/api/posts', 2, ['data' => ['type' => 'posts', 'attributes' => ['content' => 'Precog reply @"other"#p1'], 'relationships' => ['discussion' => ['data' => ['type' => 'discussions', 'id' => '1']]]]]],
            ['POST', '/api/groups', 1, ['data' => ['type' => 'groups', 'attributes' => ['nameSingular' => 'precog', 'namePlural' => 'precogs']]]],
            ['POST', '/api/tags', 1, ['data' => ['type' => 'tags', 'attributes' => ['name' => 'Precog', 'slug' => 'precog', 'color' => '#ff0000']]]],
            ['POST', '/api/flags', 2, ['data' => ['type' => 'flags', 'attributes' => ['reason' => 'spam'], 'relationships' => ['post' => ['data' => ['type' => 'posts', 'id' => '1']]]]]],
            ['POST', '/api/access-tokens', 2, ['data' => ['type' => 'access-tokens', 'attributes' => ['title' => 'precog token']]]],
            ['POST', '/api/access-tokens', 1, ['data' => ['type' => 'access-tokens', 'attributes' => ['title' => 'precog token']]]],
            ['POST', '/api/user-erasure-requests', 2, ['data' => ['type' => 'user-erasure-requests', 'attributes' => ['reason' => 'bye']], 'meta' => ['password' => 'too-obscure']]],
            ['POST', '/api/dialog-messages', 2, ['data' => ['type' => 'dialog-messages', 'attributes' => ['content' => 'precog dm', 'users' => [['id' => 3]]]]]],
            ['POST', '/api/dialog-messages', 2, ['data' => ['type' => 'dialog-messages', 'attributes' => ['content' => 'precog dm'], 'relationships' => ['dialog' => ['data' => ['type' => 'dialogs', 'id' => '1']]]]]],
            ['POST', '/api/oauth-provider-clients', 1, ['data' => ['type' => 'oauth-provider-clients', 'attributes' => ['name' => 'Precog', 'redirectUris' => ['https://example.com/cb']]]]],
            ['PATCH', '/api/users/2', 2, ['data' => ['type' => 'users', 'id' => '2', 'attributes' => ['email' => 'changed@machine.local', 'preferences' => ['followAfterReply' => true]]], 'meta' => ['password' => 'too-obscure']]],
            ['PATCH', '/api/users/3', 1, ['data' => ['type' => 'users', 'id' => '3', 'attributes' => ['username' => 'renamed', 'email' => 'renamed@machine.local', 'password' => 'new-password-1', 'isEmailConfirmed' => true, 'nickname' => 'Nick', 'suspendedUntil' => Carbon::now()->addDay()->toIso8601String(), 'suspendReason' => 'x'], 'relationships' => ['groups' => ['data' => [['type' => 'groups', 'id' => '4']]]]]]],
            ['PATCH', '/api/discussions/1', 1, ['data' => ['type' => 'discussions', 'id' => '1', 'attributes' => ['title' => 'Renamed', 'isHidden' => true, 'isLocked' => true, 'isSticky' => true, 'subscription' => 'follow', 'lastReadPostNumber' => 1], 'relationships' => ['tags' => ['data' => [['type' => 'tags', 'id' => '1']]]]]]],
            ['PATCH', '/api/posts/1', 1, ['data' => ['type' => 'posts', 'id' => '1', 'attributes' => ['content' => 'Edited', 'isHidden' => true, 'isLiked' => true, 'isApproved' => true]]]],
            ['PATCH', '/api/posts/1', 2, ['data' => ['type' => 'posts', 'id' => '1', 'attributes' => ['isLiked' => true]]]],
            ['PATCH', '/api/groups/1', 1, ['data' => ['type' => 'groups', 'id' => '1', 'attributes' => ['nameSingular' => 'Boss', 'namePlural' => 'Bosses']]]],
            ['PATCH', '/api/tags/1', 1, ['data' => ['type' => 'tags', 'id' => '1', 'attributes' => ['name' => 'Renamed']]]],
            ['PATCH', '/api/notifications/1', 2, ['data' => ['type' => 'notifications', 'id' => '1', 'attributes' => ['isRead' => true]]]],
            ['PATCH', '/api/dialogs/1', 2, ['data' => ['type' => 'dialogs', 'id' => '1', 'attributes' => ['lastReadMessageId' => 1]]]],
            ['PATCH', '/api/user-erasure-requests/1', 1, ['data' => ['type' => 'user-erasure-requests', 'id' => '1', 'attributes' => ['processedMode' => 'anonymization']]]],
        ];

        $failures = [];
        $answered = [];

        foreach ($cases as [$method, $path, $as, $json]) {
            $request = $this->precognitive($this->jsonRequest($method, $path, $json, $as));

            $db = $this->databaseFingerprint();
            $files = $this->fileFingerprint();
            $events = [];

            $response = $this->send($request);
            $key = "$method $path as ".($as ?? 'guest');
            $answered[$key] = $response->getStatusCode();

            if ($response->getStatusCode() >= 500) {
                $failures[] = "$key: HTTP {$response->getStatusCode()}";
            }

            $diff = array_keys(array_diff_assoc($this->databaseFingerprint(), $db));
            if ($diff) {
                $failures[] = "$key: changed ".implode(', ', $diff);
            }

            if ($this->fileFingerprint() !== $files) {
                $failures[] = "$key: changed files";
            }

            $writes = array_filter($events, fn (string $e) => (bool) preg_match('/^eloquent\.(creat|sav|updat|delet)/', $e) && ! str_contains($e, 'AccessToken') && ! str_ends_with($e, 'Flarum\User\User')
                || str_contains($e, 'Mail\Events') || (bool) preg_match('/\\\\Event\\\\(Saving|Saved|Creat|Updat|Delet|Posted|Started|Registered|Renamed|Hidden|Flagged|Liked|Sent)/', $e));

            if ($writes) {
                $failures[] = "$key: dispatched ".implode(', ', array_unique($writes));
            }
        }

        if ($dump = getenv('PRECOG_DUMP')) {
            file_put_contents($dump, json_encode($answered, JSON_PRETTY_PRINT)."\n", FILE_APPEND);
        }

        $this->assertSame([], $failures, implode("\n", $failures));

        // Detector sanity: the same group create sent normally is seen.
        $db = $this->databaseFingerprint();
        $events = [];
        $this->assertEquals(201, $this->send($this->jsonRequest('POST', '/api/groups', $cases[4][3], 1))->getStatusCode());
        $this->assertNotEmpty(array_diff_assoc($this->databaseFingerprint(), $db));
        $this->assertContains('Flarum\Group\Event\Saving', $events);
    }
}
