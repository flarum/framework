<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\api\users;

use Flarum\Group\Group;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

/**
 * The `joinedAt` and `lastSeenAt` date range filters: `from..to`, either side may be empty.
 */
class DateRangeFilterTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->prepareDatabase([
            User::class => [
                $this->normalUser(),
                ['id' => 3, 'username' => 'early', 'email' => 'early@machine.local', 'is_email_confirmed' => 1, 'joined_at' => '2024-01-10 08:00:00', 'last_seen_at' => '2024-06-01 12:00:00', 'preferences' => json_encode(['discloseOnline' => false])],
                ['id' => 4, 'username' => 'middle', 'email' => 'middle@machine.local', 'is_email_confirmed' => 1, 'joined_at' => '2024-03-15 23:30:00', 'last_seen_at' => '2025-01-01 00:00:00'],
                ['id' => 5, 'username' => 'late', 'email' => 'late@machine.local', 'is_email_confirmed' => 1, 'joined_at' => '2024-06-30 10:00:00', 'last_seen_at' => null],
                ['id' => 6, 'username' => 'moderator', 'email' => 'moderator@machine.local', 'is_email_confirmed' => 1, 'joined_at' => '2020-01-01 00:00:00'],
            ],
            'group_user' => [
                ['user_id' => 6, 'group_id' => Group::MODERATOR_ID],
            ],
        ]);
    }

    /**
     * @return list<int> the ids of the fixture users 3–5 in the response
     */
    private function ids(array $filter, int $actorId = 1, bool $negate = false): array
    {
        $key = array_key_first($filter);

        $response = $this->send(
            $this->request('GET', '/api/users', ['authenticatedAs' => $actorId])
                ->withQueryParams(['filter' => [($negate ? '-' : '').$key => $filter[$key]]])
        );

        $body = (string) $response->getBody();
        $this->assertEquals(200, $response->getStatusCode(), $body);

        $ids = array_map('intval', array_column(json_decode($body, true)['data'], 'id'));
        sort($ids);

        return array_values(array_intersect($ids, [3, 4, 5]));
    }

    public static function joinedAtRanges(): array
    {
        return [
            'closed range' => ['2024-01-01..2024-03-15', [3, 4]],
            'bounds are whole days' => ['2024-03-15..2024-03-15', [4]],
            'open end' => ['2024-03-01..', [4, 5]],
            'open start' => ['..2024-03-14', [3]],
            'single day' => ['2024-06-30', [5]],
            'empty range' => ['2023-01-01..2023-12-31', []],
            'the end bound includes its whole last day across a month end' => ['2024-03-16..2024-06-30', [5]],
            'the day after the end bound is excluded' => ['..2024-06-29', [3, 4]],
        ];
    }

    #[Test]
    #[DataProvider('joinedAtRanges')]
    public function joined_at_filters_by_day_range(string $value, array $expected)
    {
        $this->assertSame($expected, $this->ids(['joinedAt' => $value]));
    }

    #[Test]
    public function joined_at_can_be_negated()
    {
        $this->assertSame([5], $this->ids(['joinedAt' => '..2024-03-15'], 1, true));
    }

    #[Test]
    public function a_negated_range_includes_rows_where_the_column_was_never_set()
    {
        // User 5 was never seen: not within the range, so a negated filter matches them.
        $this->assertSame([4, 5], $this->ids(['lastSeenAt' => '2024-05-01..2024-12-31'], 1, true));
    }

    #[Test]
    public function joined_at_is_available_to_members()
    {
        $this->assertSame([3], $this->ids(['joinedAt' => '..2024-01-31'], 2));
    }

    public static function invalidValues(): array
    {
        return [
            'not a date' => ['yesterday'],
            'impossible date' => ['2024-02-30..'],
            'both sides empty' => ['..'],
            'wrong format' => ['01/02/2024..'],
        ];
    }

    #[Test]
    #[DataProvider('invalidValues')]
    public function a_malformed_range_is_a_validation_error(string $value)
    {
        $response = $this->send(
            $this->request('GET', '/api/users', ['authenticatedAs' => 1])
                ->withQueryParams(['filter' => ['joinedAt' => $value]])
        );

        $this->assertEquals(422, $response->getStatusCode(), (string) $response->getBody());
    }

    #[Test]
    public function last_seen_at_filters_for_admins_including_hidden_last_seen_times()
    {
        $this->assertSame([3], $this->ids(['lastSeenAt' => '2024-05-01..2024-12-31']));
        $this->assertSame([3, 4], $this->ids(['lastSeenAt' => '2024-01-01..']));
    }

    #[Test]
    public function last_seen_at_filters_for_moderators()
    {
        // Moderators hold `user.viewLastSeenAt` by default.
        $this->assertSame([4], $this->ids(['lastSeenAt' => '2024-12-01..'], 6));
    }

    #[Test]
    public function last_seen_at_is_ignored_without_the_permission()
    {
        // User 3 hides their last seen time: a member must not learn it by narrowing ranges.
        foreach (['2024-06-01', '..2024-06-01', '2024-06-02..', '2024-05-01..2024-12-31'] as $range) {
            $this->assertSame([3, 4, 5], $this->ids(['lastSeenAt' => $range], 2), $range);
            $this->assertSame([3, 4, 5], $this->ids(['lastSeenAt' => $range], 2, true), '-'.$range);
        }
    }

    #[Test]
    public function edge_values_are_either_valid_or_a_validation_error_and_reach_sql_only_as_bindings()
    {
        $marker = 'zz_marker';
        $values = [
            '..9999-12-31' => 200,
            '9999-12-31..9999-12-31' => 200,
            '0001-01-01..' => 200,
            '2024-12-31..2024-01-01' => 200,
            "2024-01-01\n.." => 200,
            '0000-01-01..' => 422,
            '2023-02-29..' => 422,
            '2024-01-01..2024-02-01..2024-03-01' => 422,
            '２０２４-01-01..' => 422,
            '2024-01-01T00:00:00Z..' => 422,
            "2024-01-01' OR '1'='1.." => 422,
            "2024-01-01'; DROP TABLE users; -- $marker" => 422,
            str_repeat('2024-01-01', 2000) => 422,
        ];

        $this->app();
        $db = $this->database();

        foreach ($values as $value => $status) {
            foreach (['joinedAt', '-lastSeenAt'] as $key) {
                $db->flushQueryLog();
                $db->enableQueryLog();

                $response = $this->send(
                    $this->request('GET', '/api/users', ['authenticatedAs' => 1])->withQueryParams(['filter' => [$key => (string) $value]])
                );

                $log = $db->getQueryLog();
                $db->disableQueryLog();

                $this->assertSame($status, $response->getStatusCode(), "$key=$value: ".substr((string) $response->getBody(), 0, 500));

                foreach ($log as $query) {
                    $this->assertStringNotContainsString($marker, $query['query']);
                    $this->assertStringNotContainsString("'1'='1", $query['query']);
                }
            }
        }

        $array = $this->send(
            $this->request('GET', '/api/users', ['authenticatedAs' => 1])->withQueryParams(['filter' => ['joinedAt' => ['2024-01-01', '..']]])
        );

        $this->assertLessThan(500, $array->getStatusCode());
    }
}
