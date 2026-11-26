<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Audit\Tests\integration;

use Flarum\Audit\Testing\TestCase;
use Flarum\Group\Group;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\User\User;
use Illuminate\Support\Arr;
use PHPUnit\Framework\Attributes\Test;

/**
 * Exercises the audit search filters (the 2.x replacement for the 1.x gambits). These guard
 * that `filter[<key>]` actually narrows the result set — a regression here previously returned
 * the full log because the frontend sent everything as a fulltext `q` and the backend filters
 * were keyed by `filter[action]`/`filter[actor]`/etc., not parsed out of `q`.
 */
class FilterTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    public function setUp(): void
    {
        parent::setUp();

        $this->setting('flarum-audit.limitedIpAddress', '0');
        $this->setting('flarum-audit.limitedActions', '');

        $this->prepareDatabase([
            'audit_log' => [
                [
                    'id' => 1,
                    'actor_id' => 1,
                    'client' => 'session',
                    'ip_address' => '10.0.0.1',
                    'action' => 'extension.enabled',
                    'payload' => json_encode(['package' => 'flarum-tags']),
                    'created_at' => '2022-01-01 12:00:00',
                ],
                [
                    'id' => 2,
                    'actor_id' => 1,
                    'client' => 'session',
                    'ip_address' => '10.0.0.2',
                    'action' => 'extension.disabled',
                    'payload' => json_encode(['package' => 'flarum-tags']),
                    'created_at' => '2022-01-01 13:00:00',
                ],
                [
                    'id' => 3,
                    'actor_id' => 2,
                    'client' => 'api_key',
                    'ip_address' => '10.0.0.3',
                    'action' => 'cache_cleared',
                    'payload' => null,
                    'created_at' => '2022-01-01 14:00:00',
                ],
                [
                    'id' => 4,
                    'actor_id' => 1,
                    'client' => 'session',
                    'ip_address' => '10.0.0.4',
                    'action' => 'discussion.locked',
                    'payload' => json_encode(['discussion_id' => 5]),
                    'created_at' => '2022-01-01 15:00:00',
                ],
                [
                    'id' => 5,
                    'actor_id' => 1,
                    'client' => 'session',
                    'ip_address' => '10.0.0.5',
                    'action' => 'discussion.renamed',
                    'payload' => json_encode(['discussion_id' => 6]),
                    'created_at' => '2022-01-01 16:00:00',
                ],
                [
                    'id' => 6,
                    'actor_id' => 1,
                    'client' => 'session',
                    'ip_address' => '10.0.0.6',
                    'action' => 'user.activated',
                    'payload' => json_encode(['user_id' => 2]),
                    'created_at' => '2022-01-01 17:00:00',
                ],
                [
                    'id' => 7,
                    'actor_id' => 1,
                    'client' => 'session',
                    'ip_address' => '10.0.0.7',
                    'action' => 'message.deleted',
                    'payload' => json_encode(['user_id' => null]),
                    'created_at' => '2022-01-01 18:00:00',
                ],
                [
                    'id' => 8,
                    'actor_id' => 1,
                    'client' => 'session',
                    'ip_address' => '10.0.0.8',
                    'action' => 'post.deleted',
                    'payload' => json_encode(['discussion_id' => null]),
                    'created_at' => '2022-01-01 19:00:00',
                ],
                [
                    'id' => 9,
                    'actor_id' => 1,
                    'client' => 'session',
                    'ip_address' => '10.0.0.9',
                    'action' => 'user.activated',
                    'payload' => json_encode(['user_id' => 1]),
                    'created_at' => '2022-01-01 20:00:00',
                ],
            ],
            User::class => [
                $this->normalUser(),
            ],
            'group_user' => [
                ['user_id' => 2, 'group_id' => Group::ADMINISTRATOR_ID],
            ],
        ]);
    }

    /**
     * @return array<array<string, mixed>> The `data` array of the JSON:API response.
     */
    protected function search(array $filter): array
    {
        $response = $this->send(
            $this->request('GET', '/api/audit', ['authenticatedAs' => 1])
                ->withQueryParams(['filter' => $filter])
        );

        $this->assertEquals(200, $response->getStatusCode(), 'Assert request status code');

        return Arr::get(json_decode($response->getBody()->getContents(), true), 'data') ?? [];
    }

    /**
     * @param array<array<string, mixed>> $data
     * @return string[] The ids in the result set.
     */
    protected function ids(array $data): array
    {
        return Arr::pluck($data, 'id');
    }

    #[Test]
    public function no_filter_returns_everything()
    {
        $this->assertCount(9, $this->search([]));
    }

    #[Test]
    public function action_filter_narrows_to_a_single_action()
    {
        $data = $this->search(['action' => 'extension.enabled']);

        $this->assertEquals(['1'], $this->ids($data));
    }

    #[Test]
    public function action_filter_accepts_comma_separated_values()
    {
        $data = $this->search(['action' => 'extension.enabled,extension.disabled']);

        $this->assertEqualsCanonicalizing(['1', '2'], $this->ids($data));
    }

    #[Test]
    public function negated_action_filter_excludes_matches()
    {
        $data = $this->search(['-action' => 'cache_cleared']);

        $this->assertEqualsCanonicalizing(['1', '2', '4', '5', '6', '7', '8', '9'], $this->ids($data));
    }

    #[Test]
    public function client_filter_narrows_by_client_type()
    {
        $data = $this->search(['client' => 'api_key']);

        $this->assertEquals(['3'], $this->ids($data));
    }

    #[Test]
    public function ip_filter_narrows_by_ip_address()
    {
        $data = $this->search(['ip' => '10.0.0.2']);

        $this->assertEquals(['2'], $this->ids($data));
    }

    #[Test]
    public function discussion_filter_narrows_by_payload_discussion_id()
    {
        $data = $this->search(['discussion' => '5']);

        $this->assertEquals(['4'], $this->ids($data));
    }

    #[Test]
    public function discussion_filter_accepts_comma_separated_values()
    {
        $data = $this->search(['discussion' => '5,6']);

        $this->assertEqualsCanonicalizing(['4', '5'], $this->ids($data));
    }

    #[Test]
    public function negated_discussion_filter_excludes_matches()
    {
        $data = $this->search(['-discussion' => '5']);

        $this->assertEqualsCanonicalizing(['1', '2', '3', '5', '6', '7', '8', '9'], $this->ids($data));
    }

    #[Test]
    public function user_filter_narrows_by_payload_user_id()
    {
        $data = $this->search(['user' => 'normal']);

        $this->assertEquals(['6'], $this->ids($data));
    }

    #[Test]
    public function negated_user_filter_keeps_entries_about_no_user()
    {
        $data = $this->search(['-user' => 'normal']);

        $this->assertEqualsCanonicalizing(['1', '2', '3', '4', '5', '7', '8', '9'], $this->ids($data));
    }

    #[Test]
    public function user_filter_with_unknown_username_matches_nothing()
    {
        $this->assertEquals([], $this->search(['user' => 'nobody']));
    }

    #[Test]
    public function negated_user_filter_with_unknown_username_excludes_nothing()
    {
        $this->assertCount(9, $this->search(['-user' => 'nobody']));
    }

    #[Test]
    public function negated_user_filter_ignores_unknown_usernames_among_known_ones()
    {
        $data = $this->search(['-user' => 'normal,nobody']);

        $this->assertEqualsCanonicalizing(['1', '2', '3', '4', '5', '7', '8', '9'], $this->ids($data));
    }
}
