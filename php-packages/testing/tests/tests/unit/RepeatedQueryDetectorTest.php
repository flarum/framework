<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Testing\Tests\unit;

use Flarum\Testing\integration\RepeatedQueryDetector;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class RepeatedQueryDetectorTest extends TestCase
{
    /**
     * @param array<array{0: string, 1: array}> $queries
     */
    private function log(array $queries): array
    {
        return array_map(fn ($q) => ['query' => $q[0], 'bindings' => $q[1]], $queries);
    }

    #[Test]
    public function a_relationship_loaded_per_model_is_reported()
    {
        // The shape of a real N+1: one query per record, different binding
        // each time. (Taken from a post listing loading each post's warnings.)
        $queries = [];

        for ($id = 100; $id < 110; $id++) {
            $queries[] = ['select * from `warnings` where `warnings`.`post_id` = ?', [$id]];
        }

        $repeats = RepeatedQueryDetector::findRepeats($this->log($queries), 5);

        $this->assertCount(1, $repeats);
        $this->assertSame(10, $repeats[0]['count']);
        $this->assertSame(10, $repeats[0]['distinctBindings']);
    }

    #[Test]
    public function batched_loads_of_differing_sizes_are_one_shape_not_an_n_plus_one()
    {
        // Eager loading emits `in (...)` lists of whatever length; those are
        // the fix for an N+1, not an instance of one.
        $repeats = RepeatedQueryDetector::findRepeats($this->log([
            ['select * from `users` where `users`.`id` in (1, 2, 3)', []],
            ['select * from `users` where `users`.`id` in (4, 5)', []],
        ]), 5);

        $this->assertSame([], $repeats);
    }

    #[Test]
    public function a_handful_of_distinct_queries_is_not_reported()
    {
        $repeats = RepeatedQueryDetector::findRepeats($this->log([
            ['select * from `discussions` where `id` = ?', [1]],
            ['select * from `posts` where `discussion_id` = ?', [1]],
            ['select * from `users` where `id` in (?, ?)', [1, 2]],
        ]), 5);

        $this->assertSame([], $repeats);
    }

    #[Test]
    public function repeats_below_the_threshold_are_left_alone()
    {
        // Small fixed-size loops are everywhere in a request (the actor, a
        // couple of authors) and must not be flagged.
        $queries = [];

        for ($id = 1; $id <= 4; $id++) {
            $queries[] = ['select * from `groups` where `user_id` = ?', [$id]];
        }

        $this->assertSame([], RepeatedQueryDetector::findRepeats($this->log($queries), 5));
    }

    #[Test]
    public function the_same_query_with_the_same_bindings_is_distinguished_from_an_n_plus_one()
    {
        // A missed memoisation: the same rows fetched repeatedly. Worth
        // reporting, but the binding count tells the reader it does NOT grow
        // with the data — the distinction that stops false N+1 diagnoses.
        $queries = array_fill(0, 6, ['select * from `settings` where `key` = ?', ['foo']]);

        $repeats = RepeatedQueryDetector::findRepeats($this->log($queries), 5);

        $this->assertCount(1, $repeats);
        $this->assertSame(6, $repeats[0]['count']);
        $this->assertSame(1, $repeats[0]['distinctBindings']);
    }

    #[Test]
    public function harness_transactions_are_ignored()
    {
        $queries = array_fill(0, 10, ['SAVEPOINT trans1', []]);
        $queries[] = ['BEGIN', []];

        $this->assertSame([], RepeatedQueryDetector::findRepeats($this->log($queries), 5));
    }

    #[Test]
    public function inlined_values_normalise_to_the_same_shape_as_placeholders()
    {
        // Some code paths inline values instead of binding them; they are the
        // same shape and must count together.
        $repeats = RepeatedQueryDetector::findRepeats($this->log([
            ['select * from `posts` where `id` = 1', []],
            ['select * from `posts` where `id` = 2', []],
            ['select * from `posts` where `id` = ?', [3]],
            ['select * from `posts` where `id` = ?', [4]],
            ['select * from `posts` where `id` = ?', [5]],
        ]), 5);

        $this->assertCount(1, $repeats);
        $this->assertSame(5, $repeats[0]['count']);
    }

    #[Test]
    public function the_worst_offender_is_reported_first()
    {
        $queries = [];

        for ($i = 0; $i < 6; $i++) {
            $queries[] = ['select * from `flags` where `post_id` = ?', [$i]];
        }
        for ($i = 0; $i < 12; $i++) {
            $queries[] = ['select * from `warnings` where `post_id` = ?', [$i]];
        }

        $repeats = RepeatedQueryDetector::findRepeats($this->log($queries), 5);

        $this->assertCount(2, $repeats);
        $this->assertStringContainsString('warnings', $repeats[0]['sql']);
        $this->assertSame(12, $repeats[0]['count']);
    }

    #[Test]
    public function one_query_per_record_scales_with_the_data()
    {
        // The N+1 signature: as many distinct values as executions. Add rows,
        // add queries — this is the defect that gets worse as a forum grows.
        $this->assertTrue(RepeatedQueryDetector::scalesWithData(['count' => 10, 'distinctBindings' => 10]));

        // One duplicate is tolerated: batch loaders and permission checks
        // often re-read a single value while still being per-record work.
        $this->assertTrue(RepeatedQueryDetector::scalesWithData(['count' => 10, 'distinctBindings' => 9]));
    }

    #[Test]
    public function repeating_a_handful_of_values_does_not_scale_with_the_data()
    {
        // Wasteful, but bounded: five queries for two users stays five queries
        // whether the forum has two users or two million.
        $this->assertFalse(RepeatedQueryDetector::scalesWithData(['count' => 5, 'distinctBindings' => 2]));
        $this->assertFalse(RepeatedQueryDetector::scalesWithData(['count' => 8, 'distinctBindings' => 4]));
        $this->assertFalse(RepeatedQueryDetector::scalesWithData(['count' => 6, 'distinctBindings' => 1]));
    }

    #[Test]
    public function the_description_names_counts_and_binding_diversity()
    {
        $description = RepeatedQueryDetector::describe([
            ['sql' => 'select * from `warnings` where `post_id` = ?', 'count' => 10, 'distinctBindings' => 10],
        ]);

        $this->assertStringContainsString('10x', $description);
        $this->assertStringContainsString('10 distinct bindings', $description);
        $this->assertStringContainsString('warnings', $description);
    }
}
