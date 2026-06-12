<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Audit\Tests\integration;

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
                    'payload' => json_encode([]),
                    'created_at' => '2022-01-01 14:00:00',
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
        $this->assertCount(3, $this->search([]));
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

        $this->assertEqualsCanonicalizing(['1', '2'], $this->ids($data));
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
}
