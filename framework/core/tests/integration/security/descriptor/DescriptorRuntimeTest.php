<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\security\descriptor;

use Flarum\Api\Describe\FieldDescriber;
use Flarum\Api\Describe\ResourceDescriber;
use Flarum\Group\Group;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\Test;

// Only test files are loaded by path; the root autoloader maps Flarum\Tests elsewhere.
require_once __DIR__.'/AbstractDescriptorSecurityTestCase.php';

/**
 * Security: describe-time cost, request influence and cross-actor caching.
 */
class DescriptorRuntimeTest extends AbstractDescriptorSecurityTestCase
{
    /** Upper bound on queries for one descriptor request, auth included. */
    private const int MAX_QUERIES = 40;

    private function queriesFor(string $type, int $actorId): array
    {
        $db = $this->database();
        $db->flushQueryLog();
        $db->enableQueryLog();

        [$status, , $body] = $this->describeRaw($type, $actorId);

        $this->assertSame(200, $status, $body);

        $queries = array_column($db->getQueryLog(), 'query');
        $db->disableQueryLog();

        return $queries;
    }

    #[Test]
    public function query_count_is_bounded_and_does_not_scale_with_rows()
    {
        // Warm up: the first request of the process loads settings and extensions.
        foreach (self::ACTORS as $id) {
            foreach (['users', 'groups', 'access-tokens'] as $type) {
                $this->queriesFor($type, $id);
            }
        }

        $counts = [];

        foreach (self::ACTORS as $actor => $id) {
            foreach (['users', 'groups', 'access-tokens'] as $type) {
                $counts["$actor $type"] = count($this->queriesFor($type, $id));
                $this->assertLessThanOrEqual(self::MAX_QUERIES, $counts["$actor $type"], "$actor $type");
            }
        }

        // Grow every described table: the descriptor reads no rows, so nothing changes.
        $users = $groups = $pivot = [];

        for ($i = 100; $i < 300; $i++) {
            $users[] = ['id' => $i, 'username' => "bulk$i", 'email' => "bulk$i@machine.local", 'is_email_confirmed' => 1, 'password' => 'x', 'joined_at' => '2024-01-01 00:00:00'];
            $groups[] = ['id' => $i, 'name_singular' => "G$i", 'name_plural' => "G$i", 'is_hidden' => $i % 2];
            $pivot[] = ['user_id' => $i, 'group_id' => $i];
        }

        $this->database()->table('users')->insert($users);
        $this->database()->table('groups')->insert($groups);
        $this->database()->table('group_user')->insert($pivot);

        foreach (self::ACTORS as $actor => $id) {
            foreach (['users', 'groups', 'access-tokens'] as $type) {
                $this->assertSame($counts["$actor $type"], count($this->queriesFor($type, $id)), "$actor $type scales with rows");
            }
        }
    }

    #[Test]
    public function query_params_headers_and_cookies_do_not_change_the_descriptor()
    {
        foreach (self::ACTORS as $id) {
            foreach (['users', 'groups', 'access-tokens'] as $type) {
                [, $baseline] = $this->describeRaw($type, $id);

                $request = $this->request('GET', '/api/resource-schemas/'.$type, [
                    'authenticatedAs' => $id,
                    'cookies' => ['locale' => 'de'],
                ])
                    ->withQueryParams([
                        'filter' => ['email' => 'x', 'q' => 'y'],
                        'include' => 'groups',
                        'sort' => '-email',
                        'page' => ['limit' => 1000],
                        'authenticatedAs' => 1,
                    ])
                    ->withHeader('X-HTTP-Method-Override', 'PATCH')
                    ->withHeader('Accept-Language', 'de')
                    ->withHeader('X-Forwarded-For', '127.0.0.1')
                    ->withHeader('X-CSRF-Token', 'nope');

                $response = $this->send($request);
                $body = (string) $response->getBody();
                $status = $response->getStatusCode();

                // Unsupported query params may be rejected; they must never alter a 200.
                $this->assertLessThan(500, $status, $body);

                if ($status === 200) {
                    $this->assertSame($baseline, json_decode($body, true)['data']['attributes'], "actor $id $type");
                }
            }
        }
    }

    #[Test]
    public function a_precognitive_get_is_not_a_500()
    {
        $response = $this->send(
            $this->request('GET', '/api/resource-schemas/users', ['authenticatedAs' => 2])
                ->withHeader('Precognition', 'true')
                ->withHeader('Precognition-Validate-Only', 'email')
        );

        $this->assertLessThan(500, $response->getStatusCode(), (string) $response->getBody());
    }

    #[Test]
    public function actors_interleaved_in_one_app_instance_each_get_their_own_descriptor()
    {
        $reference = [];

        // First pass: one actor at a time, each in a fresh order.
        foreach (self::ACTORS as $id) {
            $reference[$id] = $this->describe('users', $id);
        }

        // Interleave on the same booted app: admin → member → admin → moderator ...
        foreach ([1, 2, 1, 3, 2, 4, 1, 3] as $id) {
            $this->assertSame($reference[$id], $this->describe('users', $id), "actor $id got another actor's descriptor");
        }

        $this->assertNotSame($reference[1], $reference[2]);
    }

    #[Test]
    public function the_describers_are_built_per_request_so_no_memo_is_shared()
    {
        $container = $this->app()->getContainer();

        $this->assertNotSame($container->make(ResourceDescriber::class), $container->make(ResourceDescriber::class));
        $this->assertNotSame($container->make(FieldDescriber::class), $container->make(FieldDescriber::class));
    }

    #[Test]
    public function the_response_is_not_publicly_cacheable_and_carries_no_etag()
    {
        $response = $this->send($this->request('GET', '/api/resource-schemas/users', ['authenticatedAs' => 1]));

        $this->assertSame(200, $response->getStatusCode());
        $this->assertFalse($response->hasHeader('ETag'));
        $this->assertStringNotContainsString('public', strtolower($response->getHeaderLine('Cache-Control')));
    }

    #[Test]
    public function describing_as_a_user_who_becomes_admin_mid_process_reflects_the_change()
    {
        $before = $this->describe('users', 2);
        $this->assertArrayNotHasKey('email', $this->filtersOf($before));

        $this->database()->table('group_user')->insert(['user_id' => 2, 'group_id' => Group::ADMINISTRATOR_ID]);
        User::find(2)->unsetRelation('groups');

        $after = $this->describe('users', 2);
        $this->assertArrayHasKey('email', $this->filtersOf($after));
    }
}
