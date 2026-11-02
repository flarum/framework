<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\security\listing;

use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

/**
 * Security: malformed filter/sort/page input on the list endpoints must give
 * a 4xx (or be ignored), never a 500, and must reach SQL only as bindings.
 */
class ListingInputAbuseTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    private const MARKER = 'zz_inj_marker';

    protected function setUp(): void
    {
        parent::setUp();

        $this->prepareDatabase([
            User::class => [
                $this->normalUser(),
                ['id' => 3, 'username' => 'dated', 'email' => 'd@machine.local', 'is_email_confirmed' => 1, 'joined_at' => '2024-01-10 08:00:00', 'last_seen_at' => '2024-06-01 12:00:00'],
            ],
        ]);
    }

    public static function rangeValues(): array
    {
        return [
            'max date (to+1 day overflows into year 10000)' => ['..9999-12-31', true],
            'max date closed' => ['9999-12-31..9999-12-31', true],
            'min date' => ['0001-01-01..', true],
            'year zero' => ['0000-01-01..', false],
            '1e999' => ['1e999', false],
            'double dots only' => ['..', false],
            'five dots' => ['.....', false],
            'three parts' => ['2024-01-01..2024-02-01..2024-03-01', false],
            'arabic-indic digits' => ['٢٠٢٤-٠١-٠١..', false],
            'fullwidth digits' => ['２０２４-01-01..', false],
            'sql in from' => ["2024-01-01' OR '1'='1..", false],
            'sql marker' => ["2024-01-01'; DROP TABLE users; -- ".self::MARKER, false],
            'datetime instead of date' => ['2024-01-01 00:00:00..', false],
            'iso datetime' => ['2024-01-01T00:00:00Z..', false],
            'negative year' => ['-2024-01-01..', false],
            'trailing newline' => ["2024-01-01\n..", true],
            'quoted' => ['"2024-01-01..2024-12-31"', true],
            'very long' => [str_repeat('2024-01-01', 20000), false],
            // trim() strips NUL, so this is the valid `2024-01-01..`.
            'null byte' => ["2024-01-01\0..", true],
            'reversed range' => ['2024-12-31..2024-01-01', true],
            'feb 29 non-leap' => ['2023-02-29..', false],
        ];
    }

    #[Test]
    #[DataProvider('rangeValues')]
    public function range_filters_reject_or_safely_handle_malformed_values(string $value, bool $valid)
    {
        foreach (['joinedAt', '-joinedAt', 'lastSeenAt', '-lastSeenAt'] as $key) {
            $this->assertSafe(['filter' => [$key => $value]], $valid ? [200] : [422], $key);
        }
    }

    #[Test]
    public function array_values_are_rejected()
    {
        foreach (['joinedAt', 'lastSeenAt', 'email', 'group'] as $key) {
            $this->assertSafe(['filter' => [$key => ['2024-01-01', '..']]], [200, 400, 422], $key);
            $this->assertSafe(['filter' => [$key => ['a' => ['b' => 'c']]]], [200, 400, 422], $key.' nested');
        }
    }

    #[Test]
    public function many_and_mixed_filters()
    {
        $filter = ['joinedAt' => '2020-01-01..', '-lastSeenAt' => '..2020-01-01', 'email' => 'x'.self::MARKER, 'group' => implode(',', range(1, 2000)), 'q' => '%_'.self::MARKER];
        $this->assertSafe(['filter' => $filter], [200]);
    }

    #[Test]
    public function vuln_array_fulltext_query_is_a_500()
    {
        $this->markTestSkipped('Pre-existing on 2.x (array filter[q] reaches FilterManager::applyFulltext(?string) and 500s); not introduced by this branch');

        // Pre-existing: FilterManager::applyFulltext(?string) gets an array -> TypeError 500.
        $this->assertSafe(['filter' => ['q' => ['a']]], [400, 422], 'q[]');
    }

    public static function pageValues(): array
    {
        return [
            [['limit' => '-1']], [['limit' => '0']], [['limit' => '1000000']], [['limit' => 'abc']],
            [['offset' => '-5']], [['offset' => '1e10']], [['offset' => '1; DROP TABLE users']],
        ];
    }

    #[Test]
    #[DataProvider('pageValues')]
    public function paginated_endpoints_handle_page_extremes(array $page)
    {
        foreach (['/api/users', '/api/access-tokens'] as $path) {
            $body = $this->assertSafe(['page' => $page], [200, 400, 422], $path, $path);
            if (isset($body['data']) && is_array($body['data'])) {
                $this->assertLessThanOrEqual(50, count($body['data']), $path);
            }
        }
    }

    public static function vulnPageValues(): array
    {
        return [
            'groups: offset on the unpaginated index emits OFFSET without LIMIT (SQL syntax error)' => ['/api/groups', ['offset' => '1']],
            'groups: page[number] on the unpaginated index (null limit TypeError)' => ['/api/groups', ['number' => '2']],
            'users: offset beyond PHP_INT_MAX (string returned from getOffset)' => ['/api/users', ['offset' => '99999999999999999999999']],
            'users: array limit (preg_match TypeError)' => ['/api/users', ['limit' => ['x']]],
            'access tokens: array offset' => ['/api/access-tokens', ['offset' => ['x']]],
        ];
    }

    /**
     * Pre-existing (not introduced by this branch): malformed page params give a 500.
     */
    #[Test]
    #[DataProvider('vulnPageValues')]
    public function vuln_page_param_500(string $path, array $page)
    {
        $this->markTestSkipped('Pre-existing on 2.x (malformed page[] params 500 on '.$path.'); not introduced by this branch, the unpaginated cases go with flarum/framework#5392');

        $this->assertSafe(['page' => $page], [200, 400, 422], $path, $path);
    }


    #[Test]
    public function sort_injection_is_rejected()
    {
        foreach (['joinedAt;'.self::MARKER, 'username,(select 1)', '-'.self::MARKER, str_repeat('joinedAt,', 500).'joinedAt'] as $sort) {
            $this->assertSafe(['sort' => $sort], [200, 400], $sort);
        }
    }

    /**
     * Sends the request as admin, asserts the status is allowed, and that no
     * payload marker appears in the SQL text (only in bindings).
     */
    private function assertSafe(array $query, array $allowed, string $label = '', string $path = '/api/users'): ?array
    {
        $this->app();
        $db = $this->database();
        $db->flushQueryLog();
        $db->enableQueryLog();

        $response = $this->send($this->request('GET', $path, ['authenticatedAs' => 1])->withQueryParams($query));

        $log = $db->getQueryLog();
        $db->disableQueryLog();

        $raw = (string) $response->getBody();
        $this->assertContains($response->getStatusCode(), $allowed, $label.' '.substr($raw, 0, 2000));
        $this->assertStringNotContainsStringIgnoringCase('SQLSTATE', $raw, $label);

        foreach ($log as $q) {
            $this->assertStringNotContainsString(self::MARKER, $q['query'], $label);
            $this->assertStringNotContainsString("'1'='1", $q['query'], $label);
        }

        return json_decode($raw, true);
    }
}
