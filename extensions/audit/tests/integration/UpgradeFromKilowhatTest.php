<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Audit\Tests\integration;

use Flarum\Testing\integration\TestCase as BaseTestCase;
use Illuminate\Database\Schema\Builder;
use PHPUnit\Framework\Attributes\Test;

/**
 * Verifies the upgrade path from the legacy kilowhat/flarum-ext-audit (pro) and
 * kilowhat/flarum-ext-audit-free extensions, which both shipped an identical
 * `kilowhat_audit_log` table that must be renamed to `audit_log`.
 *
 * This test runs the create-table migration's `up` closure directly rather than enabling
 * the extension, so it can control whether the legacy table exists beforehand.
 */
class UpgradeFromKilowhatTest extends BaseTestCase
{
    private function schema(): Builder
    {
        return $this->app()->getContainer()->make('db')->getSchemaBuilder();
    }

    private function migration(): array
    {
        return include __DIR__.'/../../migrations/2026_06_09_000001_create_audit_log_table.php';
    }

    private function dropTables(): void
    {
        $schema = $this->schema();
        $schema->dropIfExists('audit_log');
        $schema->dropIfExists('kilowhat_audit_log');
    }

    protected function setUp(): void
    {
        parent::setUp();

        // Boot the app so the schema builder is available, then start from a clean slate.
        $this->app();
        $this->dropTables();
    }

    protected function tearDown(): void
    {
        $this->dropTables();

        // Leave a clean audit_log behind by re-running the migration, so other tests
        // (which share this database) find the table in its normal post-migration state
        // even if their ordering interleaves with this one.
        $this->migration()['up']($this->schema());

        // The migration runs DDL (create/rename/drop table) which implicitly commits the
        // transaction the harness opened and desyncs Laravel's nested-transaction counter.
        // Reconnect to reset that counter to zero, then open a fresh transaction so the
        // harness's tearDown rollBack() has a matching one to roll back.
        $db = $this->app()->getContainer()->make('db');
        $db->connection()->disconnect();
        $db->connection()->reconnect();
        $db->connection()->beginTransaction();

        parent::tearDown();
    }

    #[Test]
    public function migration_renames_legacy_table_and_preserves_data()
    {
        $schema = $this->schema();
        $db = $schema->getConnection();

        // Simulate a forum that previously ran the kilowhat extension: a populated legacy table.
        $schema->create('kilowhat_audit_log', function ($table) {
            $table->increments('id');
            $table->unsignedInteger('actor_id')->nullable()->index();
            $table->string('client')->index();
            $table->string('ip_address')->nullable()->index();
            $table->string('action')->index();
            $table->json('payload')->nullable();
            $table->dateTime('created_at');
        });

        $db->table('kilowhat_audit_log')->insert([
            'id' => 42,
            'actor_id' => 1,
            'client' => 'session',
            'ip_address' => '127.0.0.1',
            'action' => 'post.created',
            'payload' => json_encode(['post_id' => 7, 'discussion_id' => 3]),
            'created_at' => '2022-01-01 12:00:00',
        ]);

        // Run the migration.
        $this->migration()['up']($schema);

        // The new table exists, the legacy one is gone, and the row survived with its id.
        $this->assertTrue($schema->hasTable('audit_log'), 'New table should exist');
        $this->assertFalse($schema->hasTable('kilowhat_audit_log'), 'Legacy table should be renamed away');

        $rows = $db->table('audit_log')->get();
        $this->assertCount(1, $rows, 'Existing log entries should be preserved');
        $this->assertEquals(42, $rows[0]->id, 'Primary keys should be preserved by the rename');
        $this->assertEquals('post.created', $rows[0]->action);
    }

    #[Test]
    public function migration_creates_fresh_table_when_no_legacy_table()
    {
        $schema = $this->schema();

        $this->assertFalse($schema->hasTable('kilowhat_audit_log'));

        $this->migration()['up']($schema);

        $this->assertTrue($schema->hasTable('audit_log'), 'New table should be created from scratch');
        $this->assertTrue($schema->hasColumn('audit_log', 'payload'));
    }
}
