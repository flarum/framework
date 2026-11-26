<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Audit\Tests\integration;

use Flarum\Audit\Testing\TestCase;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Builder;
use PHPUnit\Framework\Attributes\Test;

class CreatedAtIndexMigrationTest extends TestCase
{
    private const MIGRATION = __DIR__.'/../../migrations/2026_10_01_000000_add_audit_log_created_at_index.php';

    private function schema(): Builder
    {
        return $this->database()->getSchemaBuilder();
    }

    private function runMigration(string $direction): void
    {
        (require self::MIGRATION)[$direction]($this->schema());
    }

    /**
     * @return list<string>
     */
    private function createdAtIndexNames(): array
    {
        $names = [];

        foreach ($this->schema()->getIndexes('audit_log') as $index) {
            if ($index['columns'] === ['created_at']) {
                $names[] = $index['name'];
            }
        }

        return $names;
    }

    private function restoreIndex(): void
    {
        // DDL isn't rolled back with the test's transaction on MySQL and MariaDB:
        // leave the index as installs have it, for the tests after this one.
        $this->schema()->table('audit_log', function (Blueprint $table) {
            foreach ($this->createdAtIndexNames() as $name) {
                $table->dropIndex($name);
            }
        });

        $this->runMigration('up');
    }

    #[Test]
    public function rolling_back_drops_the_index_it_added(): void
    {
        $this->app();

        $this->assertCount(1, $this->createdAtIndexNames());

        try {
            $this->runMigration('down');

            $this->assertSame([], $this->createdAtIndexNames());
        } finally {
            $this->restoreIndex();
        }
    }

    #[Test]
    public function rolling_back_leaves_an_index_the_forum_already_had(): void
    {
        $this->app();

        $name = strtolower($this->database()->getTablePrefix()).'audit_log_created_by_hand';

        $this->schema()->table('audit_log', function (Blueprint $table) use ($name) {
            $table->dropIndex(['created_at']);
            $table->index(['created_at'], $name);
        });

        try {
            $this->runMigration('up');
            $this->assertSame([$name], $this->createdAtIndexNames(), 'The existing index is not duplicated');

            $this->runMigration('down');
            $this->assertSame([$name], $this->createdAtIndexNames(), 'The existing index is not dropped');
        } finally {
            $this->restoreIndex();
        }
    }
}
