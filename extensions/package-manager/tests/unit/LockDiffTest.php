<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\ExtensionManager\Tests\Unit;

use Flarum\ExtensionManager\Plan\Change;
use Flarum\ExtensionManager\Plan\LockDiff;
use PHPUnit\Framework\TestCase;

/**
 * The plan is the promise. Everything downstream — how much is downloaded,
 * how much is replaced, how much a rollback costs — follows from this diff being
 * exactly right, and it is also the screen somebody approves before their site
 * changes. A wrong diff is a lie they acted on.
 */
class LockDiffTest extends TestCase
{
    private function lock(array $packages, array $dev = []): array
    {
        $shape = fn (array $p) => array_map(
            fn ($name, $version) => ['name' => $name, 'version' => $version],
            array_keys($p),
            array_values($p)
        );

        return ['packages' => $shape($packages), 'packages-dev' => $shape($dev)];
    }

    public function test_it_reports_only_what_moved(): void
    {
        $changes = (new LockDiff())->between(
            $this->lock(['a/one' => '1.0.0', 'a/two' => '2.0.0', 'a/three' => '3.0.0']),
            $this->lock(['a/one' => '1.0.0', 'a/two' => '2.1.0', 'a/three' => '3.0.0'])
        );

        $this->assertCount(1, $changes, 'unchanged packages must not appear in a plan');
        $this->assertSame('a/two', $changes[0]->package);
        $this->assertSame(Change::REPLACE, $changes[0]->op);
        $this->assertSame('2.0.0', $changes[0]->from);
        $this->assertSame('2.1.0', $changes[0]->to);
    }

    public function test_additions_and_removals_are_both_found(): void
    {
        $changes = (new LockDiff())->between(
            $this->lock(['a/keep' => '1.0.0', 'a/gone' => '1.0.0']),
            $this->lock(['a/keep' => '1.0.0', 'a/fresh' => '1.0.0'])
        );

        $ops = [];
        foreach ($changes as $c) {
            $ops[$c->package] = $c->op;
        }

        $this->assertSame([Change::ADD, Change::REMOVE], [$ops['a/fresh'], $ops['a/gone']]);
    }

    public function test_dev_dependencies_count_too(): void
    {
        // They are still files on disk. A plan that ignored them would be wrong
        // about what it is about to do.
        $changes = (new LockDiff())->between(
            $this->lock([], ['a/tool' => '1.0.0']),
            $this->lock([], ['a/tool' => '1.1.0'])
        );

        $this->assertCount(1, $changes);
        $this->assertSame('a/tool', $changes[0]->package);
    }

    public function test_the_order_is_stable(): void
    {
        // A resumed run re-derives this list and carries on at a saved index.
        // If the order moved between runs it would redo one package and skip
        // another — Composer's own ordering is not stable, so this sorts.
        $before = $this->lock(['z/last' => '1.0.0', 'a/first' => '1.0.0', 'm/mid' => '1.0.0']);
        $after = $this->lock(['z/last' => '2.0.0', 'a/first' => '2.0.0', 'm/mid' => '2.0.0']);

        $names = fn (array $cs) => array_map(fn ($c) => $c->package, $cs);

        $this->assertSame(['a/first', 'm/mid', 'z/last'], $names((new LockDiff())->between($before, $after)));
    }

    public function test_identical_locks_produce_no_plan(): void
    {
        $lock = $this->lock(['a/one' => '1.0.0']);

        $this->assertSame([], (new LockDiff())->between($lock, $lock));
    }

    public function test_dist_sources_carry_a_checksum_when_there_is_one(): void
    {
        $after = ['packages' => [[
            'name' => 'a/one', 'version' => '1.0.0',
            'dist' => ['url' => 'https://example.com/a.zip', 'type' => 'zip', 'shasum' => 'abc', 'reference' => 'deadbeef'],
        ]]];

        $sources = (new LockDiff())->sources($after);

        $this->assertSame('https://example.com/a.zip', $sources['a/one']['url']);
        $this->assertSame('abc', $sources['a/one']['shasum']);
    }

    public function test_a_package_with_no_archive_is_left_out_rather_than_faked(): void
    {
        // A path repository has no dist. Inventing a URL would fail later and
        // vaguely; omitting it lets the fetch phase name the package it cannot get.
        $after = ['packages' => [
            ['name' => 'a/normal', 'version' => '1.0.0', 'dist' => ['url' => 'https://example.com/a.zip']],
            ['name' => 'a/local', 'version' => '1.0.0'],
        ]];

        $sources = (new LockDiff())->sources($after);

        $this->assertArrayHasKey('a/normal', $sources);
        $this->assertArrayNotHasKey('a/local', $sources);
    }

    public function test_it_says_why_each_package_is_in_the_plan(): void
    {
        // "You asked for this" and "something you asked for needs it" are
        // different, and that difference is why anyone reads the screen.
        $after = ['packages' => [
            ['name' => 'fof/pwa', 'version' => '2.0.0', 'require' => ['psr/clock' => '^1.0']],
            ['name' => 'psr/clock', 'version' => '1.0.0'],
        ]];

        $diff = new LockDiff();
        $changes = [
            new Change(Change::REPLACE, 'fof/pwa', '1.0.0', '2.0.0'),
            new Change(Change::ADD, 'psr/clock', null, '1.0.0'),
        ];

        $reasons = $diff->reasons($changes, $after, ['fof/pwa']);

        $this->assertSame('you asked for this', $reasons['fof/pwa']);
        $this->assertSame('required by fof/pwa', $reasons['psr/clock']);
    }

    public function test_something_it_cannot_attribute_says_so_rather_than_guessing(): void
    {
        $after = ['packages' => [['name' => 'a/mystery', 'version' => '1.0.0']]];

        $reasons = (new LockDiff())->reasons(
            [new Change(Change::ADD, 'a/mystery', null, '1.0.0')],
            $after,
            ['a/other']
        );

        $this->assertSame('pulled in by this update', $reasons['a/mystery']);
    }
    // ── development versions: the branch moves, the version string does not ──

    /** @param array<string,array{0:string,1:string}> $packages name => [version, dist reference] */
    private function lockWithRefs(array $packages, string $where = 'dist'): array
    {
        return ['packages' => array_map(
            fn (string $name, array $p) => ['name' => $name, 'version' => $p[0], $where => ['type' => 'zip', 'url' => "https://example.test/$name.zip", 'reference' => $p[1]]],
            array_keys($packages),
            array_values($packages)
        )];
    }

    /**
     * Yesterday's 2.x-dev and today's are different code. Compared on the
     * version string alone, every nightly after the first planned nothing and
     * finished "nothing to update".
     */
    public function test_a_branch_that_moved_to_a_newer_commit_is_a_replacement(): void
    {
        $changes = (new LockDiff())->between(
            $this->lockWithRefs(['flarum/core' => ['2.x-dev', 'aaaaaaa1111111'], 'a/main' => ['dev-main', 'ccc']]),
            $this->lockWithRefs(['flarum/core' => ['2.x-dev', 'bbbbbbb2222222'], 'a/main' => ['dev-main', 'ddd']])
        );

        $this->assertCount(2, $changes);
        $this->assertSame(['a/main', 'flarum/core'], array_map(fn (Change $c) => $c->package, $changes));
        $this->assertSame(Change::REPLACE, $changes[1]->op);
        $this->assertSame('2.x-dev', $changes[1]->from);
        $this->assertSame('2.x-dev', $changes[1]->to);
        $this->assertSame('aaaaaaa1111111', $changes[1]->fromRef);
        $this->assertSame('bbbbbbb2222222', $changes[1]->toRef);
    }

    public function test_a_source_only_development_package_is_compared_on_its_source_commit(): void
    {
        $changes = (new LockDiff())->between(
            $this->lockWithRefs(['a/thing' => ['dev-main', 'one']], 'source'),
            $this->lockWithRefs(['a/thing' => ['dev-main', 'two']], 'source')
        );

        $this->assertCount(1, $changes);
        $this->assertSame('two', $changes[0]->toRef);
    }

    public function test_a_branch_on_the_same_commit_is_not_a_change(): void
    {
        $lock = $this->lockWithRefs(['flarum/core' => ['2.x-dev', 'aaaaaaa']]);

        $this->assertSame([], (new LockDiff())->between($lock, $lock));
    }

    /**
     * Only development versions. A tag whose commit changed was moved by its
     * author, and that is not something an update should act on quietly.
     */
    public function test_a_tagged_release_is_still_compared_on_its_version_alone(): void
    {
        $changes = (new LockDiff())->between(
            $this->lockWithRefs(['a/thing' => ['1.0.0', 'one']]),
            $this->lockWithRefs(['a/thing' => ['1.0.0', 'two']])
        );

        $this->assertSame([], $changes);
    }

    /** A lock with no reference recorded on one side cannot say the branch moved, so it does not guess. */
    public function test_a_missing_reference_is_not_read_as_a_move(): void
    {
        $changes = (new LockDiff())->between(
            $this->lock(['a/thing' => '2.x-dev']),
            $this->lockWithRefs(['a/thing' => ['2.x-dev', 'two']])
        );

        $this->assertSame([], $changes);
    }

    /** From a branch to a release is a version change, carrying the commit it left. */
    public function test_leaving_a_branch_for_a_release_keeps_the_commit_it_left(): void
    {
        $changes = (new LockDiff())->between(
            $this->lockWithRefs(['a/thing' => ['2.x-dev', 'one']]),
            $this->lockWithRefs(['a/thing' => ['2.0.0', 'two']])
        );

        $this->assertCount(1, $changes);
        $this->assertSame('one', $changes[0]->fromRef);
        $this->assertNull($changes[0]->toRef, 'A release is named by its version.');
    }
}
