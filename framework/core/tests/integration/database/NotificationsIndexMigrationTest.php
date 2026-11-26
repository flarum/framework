<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\database;

use Flarum\Testing\integration\TestCase;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Builder;
use PHPUnit\Framework\Attributes\Test;

class NotificationsIndexMigrationTest extends TestCase
{
    private const COLUMNS = ['user_id', 'is_deleted', 'read_at', 'type'];

    private const OLD_NAME = 'notifications_user_unread_type_index';

    private function schema(): Builder
    {
        return $this->database()->getSchemaBuilder();
    }

    /**
     * The name Laravel gives the index, with the table prefix as it would be in an index name.
     */
    private function expectedName(): string
    {
        return strtolower($this->database()->getTablePrefix()).'notifications_user_id_is_deleted_read_at_type_index';
    }

    /**
     * The names of the indexes on the notifications table over the index's columns.
     *
     * @return list<string>
     */
    private function indexNames(): array
    {
        $names = [];

        foreach ($this->schema()->getIndexes('notifications') as $index) {
            if ($index['columns'] === self::COLUMNS) {
                $names[] = strtolower($index['name']);
            }
        }

        return $names;
    }

    private function runMigration(string $migration, string $direction): void
    {
        $migration = require __DIR__."/../../../migrations/$migration.php";

        $migration[$direction]($this->schema());
    }

    #[Test]
    public function the_index_has_laravel_s_name(): void
    {
        $this->app();

        $this->assertSame([$this->expectedName()], $this->indexNames());
        $this->assertTrue($this->schema()->hasIndex('notifications', $this->expectedName()));
    }

    #[Test]
    public function an_index_from_2_0_is_renamed_on_postgresql_and_sqlite_and_rolls_back(): void
    {
        $this->app();

        // Give the index the name it had in 2.0. Dropped and created again, as MariaDB 10.3 can't rename an index.
        $this->schema()->table('notifications', function (Blueprint $table) {
            $table->dropIndex(self::COLUMNS);
            $table->index(self::COLUMNS, self::OLD_NAME);
        });

        try {
            $this->runMigration('2026_11_14_000000_prefix_notifications_composite_index_name', 'up');

            $renamed = in_array($this->database()->getDriverName(), ['pgsql', 'sqlite'], true);
            $this->assertSame([$renamed ? $this->expectedName() : self::OLD_NAME], $this->indexNames());

            $this->runMigration('2026_11_14_000000_prefix_notifications_composite_index_name', 'up');
            $this->assertSame([$renamed ? $this->expectedName() : self::OLD_NAME], $this->indexNames(), 'Running it twice changes nothing');

            $this->runMigration('2026_11_14_000000_prefix_notifications_composite_index_name', 'down');
            $this->assertSame([$renamed ? $this->expectedName() : self::OLD_NAME], $this->indexNames(), 'Rolling it back leaves the index alone');

            $this->runMigration('2026_03_25_000000_add_notifications_composite_index', 'down');
            $this->assertSame([], $this->indexNames());
        } finally {
            // DDL isn't rolled back with the test's transaction on MySQL: put the index back as installs have it.
            if ($this->indexNames() !== []) {
                $this->runMigration('2026_03_25_000000_add_notifications_composite_index', 'down');
            }

            $this->runMigration('2026_03_25_000000_add_notifications_composite_index', 'up');
        }

        $this->assertSame([$this->expectedName()], $this->indexNames());
    }
}
