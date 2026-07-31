<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tags\Tests\integration;

use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\Test;

/**
 * GlobalPolicy gates viewForum/startDiscussion on whether the actor can see
 * "enough" tags (the min_primary/min_secondary settings). This ran four
 * separate COUNT queries on every request. The verdict matrix below pins the
 * semantics; the query-count tests pin the cost: one aggregate scan in the
 * common case, the totals only when the permission counts fall short, and
 * none at all when the settings make the answer a constant.
 *
 * Representative tags: 11 primary in total of which 6 are visible without
 * tag permissions (1, 2, 3, 4 unrestricted; 7, 13 are unrestricted children
 * of restricted parents — the count only inspects each tag's own flag), and
 * 3 top-level secondary in total of which 2 are visible (9, 10; 11 is
 * restricted).
 */
class GlobalPolicyTagCountsTest extends TestCase
{
    use RetrievesAuthorizedUsers;
    use RetrievesRepresentativeTags;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-tags');

        $this->prepareDatabase([
            'tags' => $this->tags(),
            User::class => [$this->normalUser()],
        ]);
    }

    private function can(int $userId, string $ability): bool
    {
        $this->app();

        return User::query()->findOrFail($userId)->can($ability);
    }

    /**
     * Count the tag-aggregate queries issued while evaluating an ability.
     */
    private function tagCountQueries(int $userId, string $ability): int
    {
        $this->app();

        $actor = User::query()->findOrFail($userId);

        $db = $this->database();
        $db->flushQueryLog();
        $db->enableQueryLog();

        $actor->can($ability);

        $count = 0;
        foreach ($db->getQueryLog() as $query) {
            $sql = strtolower($query['query']);
            if (str_contains($sql, 'tags') && (str_contains($sql, 'count(') || str_contains($sql, 'sum('))) {
                $count++;
            }
        }

        $db->disableQueryLog();

        return $count;
    }

    // ------------------------------------------------------------------
    // Verdicts (characterization: identical before and after)
    // ------------------------------------------------------------------

    #[Test]
    public function user_with_visible_tags_can_view_forum_on_default_settings()
    {
        // Defaults: min_primary_tags = 1, min_secondary_tags = 0.
        $this->assertTrue($this->can(2, 'viewForum'));
    }

    #[Test]
    public function user_is_denied_when_fewer_visible_primary_tags_than_the_minimum()
    {
        // 6 primary tags visible without permissions, 11 in total.
        $this->setting('flarum-tags.min_primary_tags', '10');

        $this->assertFalse($this->can(2, 'viewForum'));
    }

    #[Test]
    public function admin_sees_all_tags_and_is_allowed_where_the_user_is_not()
    {
        $this->setting('flarum-tags.min_primary_tags', '10');

        $this->assertTrue($this->can(1, 'viewForum'));
    }

    #[Test]
    public function seeing_every_tag_satisfies_a_minimum_larger_than_the_forum()
    {
        // min(total, setting): a forum with fewer tags than the minimum
        // requires only that all of them are visible. Admin sees all 11.
        $this->setting('flarum-tags.min_primary_tags', '20');

        $this->assertTrue($this->can(1, 'viewForum'));
        $this->assertFalse($this->can(2, 'viewForum'));
    }

    #[Test]
    public function secondary_minimum_is_enforced_independently()
    {
        // 2 visible secondary tags, 3 top-level in total.
        $this->setting('flarum-tags.min_secondary_tags', '3');
        $this->assertFalse($this->can(2, 'viewForum'));
    }

    #[Test]
    public function secondary_minimum_within_visible_tags_is_satisfied()
    {
        $this->setting('flarum-tags.min_secondary_tags', '2');
        $this->assertTrue($this->can(2, 'viewForum'));
    }

    #[Test]
    public function zero_minimums_allow_viewing_outright()
    {
        $this->setting('flarum-tags.min_primary_tags', '0');
        $this->setting('flarum-tags.min_secondary_tags', '0');

        $this->assertTrue($this->can(2, 'viewForum'));
    }

    #[Test]
    public function start_discussion_ignores_totals_and_denies_a_too_high_minimum()
    {
        // Unlike viewForum, startDiscussion has no min(total, setting)
        // tolerance: 6 visible primary < 10 required, full stop.
        $this->setting('flarum-tags.min_primary_tags', '10');

        $this->assertFalse($this->can(2, 'startDiscussion'));
    }

    #[Test]
    public function start_discussion_allowed_when_enough_tags_are_visible()
    {
        $this->assertTrue($this->can(2, 'startDiscussion'));
    }

    // ------------------------------------------------------------------
    // Query counts (the point of the change)
    // ------------------------------------------------------------------

    #[Test]
    public function default_config_costs_one_aggregate_query()
    {
        // min_secondary = 0 needs no queries at all; min_primary = 1 is
        // satisfied by the permission-scoped count alone (6 >= 1), so the
        // totals are never fetched.
        $this->assertSame(1, $this->tagCountQueries(2, 'viewForum'));
    }

    #[Test]
    public function totals_are_fetched_only_when_the_permission_count_falls_short()
    {
        $this->setting('flarum-tags.min_primary_tags', '10');

        $this->assertSame(2, $this->tagCountQueries(2, 'viewForum'));
    }

    #[Test]
    public function zero_minimums_cost_no_queries()
    {
        $this->setting('flarum-tags.min_primary_tags', '0');
        $this->setting('flarum-tags.min_secondary_tags', '0');

        $this->assertSame(0, $this->tagCountQueries(2, 'viewForum'));
    }

    #[Test]
    public function start_discussion_costs_one_aggregate_query()
    {
        $this->assertSame(1, $this->tagCountQueries(2, 'startDiscussion'));
    }

    #[Test]
    public function repeat_checks_are_memoized_within_the_request()
    {
        $this->app();

        $actor = User::query()->findOrFail(2);

        $db = $this->database();
        $db->flushQueryLog();
        $db->enableQueryLog();

        $actor->can('viewForum');
        $actor->can('viewForum');

        $count = 0;
        foreach ($db->getQueryLog() as $query) {
            $sql = strtolower($query['query']);
            if (str_contains($sql, 'tags') && (str_contains($sql, 'count(') || str_contains($sql, 'sum('))) {
                $count++;
            }
        }

        $db->disableQueryLog();

        $this->assertSame(1, $count);
    }
}
