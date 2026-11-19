<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tags\Tests\integration;

use Flarum\Tags\Tag;
use Flarum\Testing\integration\TestCase;
use Illuminate\Database\Schema\Builder;
use PHPUnit\Framework\Attributes\Test;

/**
 * Saving a tag from the admin edit modal used to clear its is_primary flag.
 * On upgrade, every tag's flag is set again from its position, which is what
 * makes a tag primary: primary tags and their children have one, secondary
 * tags don't.
 *
 * While flags were cleared, a new primary tag was placed after only the tags
 * still flagged, so it could share a position with another. Positions are
 * numbered again within each list, keeping their order.
 */
class RepairIsPrimaryMigrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-tags');

        $this->prepareDatabase([
            Tag::class => [
                // A primary tag and its child, both saved from the edit modal.
                ['id' => 1, 'name' => 'General', 'slug' => 'general', 'position' => 0, 'parent_id' => null, 'is_primary' => false],
                ['id' => 2, 'name' => 'Child', 'slug' => 'child', 'position' => 0, 'parent_id' => 1, 'is_primary' => false],
                // Tags whose flag was already right.
                ['id' => 3, 'name' => 'Primary', 'slug' => 'primary', 'position' => 1, 'parent_id' => null, 'is_primary' => true],
                ['id' => 4, 'name' => 'Secondary', 'slug' => 'secondary', 'position' => null, 'parent_id' => null, 'is_primary' => false],
                // A secondary tag flagged primary through the API.
                ['id' => 5, 'name' => 'Flagged', 'slug' => 'flagged', 'position' => null, 'parent_id' => null, 'is_primary' => true],
                // Created after General and Primary had lost their flags.
                ['id' => 6, 'name' => 'Newer', 'slug' => 'newer', 'position' => 1, 'parent_id' => null, 'is_primary' => true],
                // Children sharing a position under General.
                ['id' => 7, 'name' => 'Second child', 'slug' => 'second-child', 'position' => 0, 'parent_id' => 1, 'is_primary' => true],
            ],
        ]);
    }

    protected function migrate(): void
    {
        $migration = include __DIR__.'/../../migrations/2026_11_10_000000_repair_is_primary_of_tags.php';

        /** @var Builder $schema */
        $schema = $this->app()->getContainer()->make('db')->getSchemaBuilder();

        $migration['up']($schema);
    }

    #[Test]
    public function is_primary_follows_the_position_of_every_tag()
    {
        $this->migrate();

        $this->assertEquals(
            [1 => true, 2 => true, 3 => true, 4 => false, 5 => false, 6 => true, 7 => true],
            Tag::query()->orderBy('id')->pluck('is_primary', 'id')->all()
        );
    }

    #[Test]
    public function tags_in_one_list_no_longer_share_a_position()
    {
        $this->migrate();

        $positions = Tag::query()->orderBy('id')->pluck('position', 'id')->all();

        // Ties keep the order of creation.
        $this->assertEquals([1 => 0, 3 => 1, 6 => 2], array_intersect_key($positions, [1 => 0, 3 => 0, 6 => 0]));
        $this->assertEquals([2 => 0, 7 => 1], array_intersect_key($positions, [2 => 0, 7 => 0]));
        $this->assertNull($positions[4]);
        $this->assertNull($positions[5]);
    }
}
