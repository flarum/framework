<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\ExtensionManager\Tests\Unit;

use Flarum\ExtensionManager\Plan\Change;
use Flarum\ExtensionManager\Tests\unit\EnglishTranslator;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ChangeTest extends TestCase
{
    /**
     * A package name is the one piece of plan data that reaches the
     * filesystem, so it is the way in. The constructor's "must contain a slash"
     * check is not enough on its own — "a/../../etc" satisfies it — which is why
     * relativePath() validates every segment rather than the string as a whole.
     */
    public static function hostileNames(): array
    {
        return [
            'parent traversal' => ['a/../../etc'],
            'leading traversal' => ['../etc/passwd'],
            'current dir segment' => ['a/./b'],
            'empty segment' => ['a//b'],
            'absolute-ish' => ['/etc/passwd'],
            'null byte' => ["a/b\0c"],
            'space and shell chars' => ['a/b;rm -rf'],
        ];
    }

    #[DataProvider('hostileNames')]
    public function test_a_hostile_package_name_never_becomes_a_path(string $name): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new Change(Change::REPLACE, $name, '1.0.0', '2.0.0'))->relativePath();
    }

    public function test_an_ordinary_package_name_maps_to_its_directory(): void
    {
        $change = new Change(Change::REPLACE, 'fof/pwa', '2.0.0-beta.3', '2.0.0-beta.4');

        $this->assertSame('fof'.DIRECTORY_SEPARATOR.'pwa', $change->relativePath());
    }

    public function test_the_trash_name_cannot_collide_across_versions(): void
    {
        // Two attempts at the same package from different versions must not land
        // in the same trash slot, or a rollback restores the wrong one.
        $a = new Change(Change::REPLACE, 'fof/pwa', '2.0.0', '2.1.0');
        $b = new Change(Change::REPLACE, 'fof/pwa', '2.1.0', '2.2.0');

        $this->assertNotSame($a->trashName(), $b->trashName());
        $this->assertStringNotContainsString('/', $a->trashName());
    }

    public function test_a_branch_version_with_a_slash_stays_one_entry_in_the_trash(): void
    {
        // flarum/framework#5357: dev-feature/x made a nested trash path.
        $this->assertSame('acme+widget@dev-feature+x', (new Change(Change::REPLACE, 'acme/widget', 'dev-feature/x', 'dev-main'))->trashName());
    }

    public function test_a_change_missing_the_versions_its_operation_needs_is_refused(): void
    {
        // Caught at construction so the applier can trust the object, rather
        // than every call site re-checking.
        $this->expectException(InvalidArgumentException::class);

        new Change(Change::REPLACE, 'a/b', null, '2.0.0');
    }

    public function test_it_survives_a_round_trip_through_the_journal(): void
    {
        // Rollback rebuilds these from what the journal recorded, so the two
        // representations have to agree exactly.
        $original = new Change(Change::REPLACE, 'fof/pwa', '2.0.0', '2.1.0');
        $restored = Change::fromArray($original->toArray());

        $this->assertSame($original->op, $restored->op);
        $this->assertSame($original->package, $restored->package);
        $this->assertSame($original->from, $restored->from);
        $this->assertSame($original->to, $restored->to);
        $this->assertSame($original->trashName(), $restored->trashName());
    }

    /**
     * Two nightlies in a row both replace 2.x-dev. Named by version alone,
     * the second one's rollback copy would land in the first one's slot.
     */
    public function test_the_trash_name_cannot_collide_across_commits_of_one_branch(): void
    {
        $first = new Change(Change::REPLACE, 'flarum/core', '2.x-dev', '2.x-dev', 'aaaaaaaaaaaaaaaa', 'bbbbbbbbbbbbbbbb');
        $second = new Change(Change::REPLACE, 'flarum/core', '2.x-dev', '2.x-dev', 'bbbbbbbbbbbbbbbb', 'cccccccccccccccc');

        $this->assertNotSame($first->trashName(), $second->trashName());
        $this->assertSame('flarum+core@2.x-dev#aaaaaaaaaaaa', $first->trashName());
    }

    /** Copies already in the trash keep their names, so a rollback still finds them. */
    public function test_a_change_without_a_commit_keeps_the_name_it_always_had(): void
    {
        $this->assertSame('fof+pwa@2.0.0', (new Change(Change::REPLACE, 'fof/pwa', '2.0.0', '2.1.0'))->trashName());
    }

    /** A reference is only ever part of a name, so nothing in it can reach the path. */
    public function test_a_hostile_reference_never_reaches_the_trash_name(): void
    {
        $change = new Change(Change::REPLACE, 'a/b', 'dev-main', 'dev-main', '../../etc/passwd', 'x');

        $this->assertStringNotContainsString('/', $change->trashName());
        $this->assertStringNotContainsString('..', $change->trashName());
    }

    public function test_the_commits_survive_a_round_trip_through_the_journal(): void
    {
        $original = new Change(Change::REPLACE, 'flarum/core', '2.x-dev', '2.x-dev', 'aaaaaaa', 'bbbbbbb');
        $restored = Change::fromArray($original->toArray());

        $this->assertSame('aaaaaaa', $restored->fromRef);
        $this->assertSame('bbbbbbb', $restored->toRef);
        $this->assertSame($original->trashName(), $restored->trashName());
    }

    /** "2.x-dev → 2.x-dev" says nothing; the commits say what moved. */
    public function test_a_move_along_a_branch_names_the_commits(): void
    {
        $change = new Change(Change::REPLACE, 'flarum/core', '2.x-dev', '2.x-dev', 'aaaaaaa1111', 'bbbbbbb2222');

        $this->assertSame('flarum/core 2.x-dev (aaaaaaa → bbbbbbb)', $change->describe(EnglishTranslator::make()));
    }
}
