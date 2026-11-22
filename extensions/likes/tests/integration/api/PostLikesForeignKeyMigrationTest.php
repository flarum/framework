<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Likes\Tests\integration\api;

use Flarum\Testing\integration\TestCase;
use Illuminate\Container\Container;
use Illuminate\Database\Connection;
use Illuminate\Database\Connectors\ConnectionFactory;
use Illuminate\Database\Schema\Blueprint;
use PHPUnit\Framework\Attributes\Test;

/**
 * SQLite forums upgraded from 1.x have a `post_likes` table without foreign
 * keys: Flarum 1.8 pinned Laravel 8, which compiled ALTER ... ADD FOREIGN KEY
 * to nothing. Core rebuilt its own tables in 2024 and left this one to the
 * likes extension.
 */
class PostLikesForeignKeyMigrationTest extends TestCase
{
    private Connection $db;

    private string $prefix;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-likes');

        if ($this->database()->getDriverName() !== 'sqlite') {
            $this->markTestSkipped('Only SQLite upgrades from 1.x lack the foreign keys.');
        }

        // DDL must never commit the application's fixture transaction. Use a
        // separate connection and private tables, as the migration test does.
        $config = $this->database()->getConfig();
        $this->prefix = 'likesfk_'.bin2hex(random_bytes(4)).'_';
        $config['prefix'] = $this->prefix;
        $config['database'] = ':memory:';
        $config['foreign_key_constraints'] = true;

        $this->db = (new ConnectionFactory(new Container))->make($config);
        $this->db->getSchemaBuilder()->create('users', function (Blueprint $table) {
            $table->increments('id');
        });
        $this->db->getSchemaBuilder()->create('posts', function (Blueprint $table) {
            $table->increments('id');
        });
    }

    protected function tearDown(): void
    {
        if (isset($this->db)) {
            $this->db->disconnect();
        }

        parent::tearDown();
    }

    #[Test]
    public function the_migration_gives_an_upgraded_post_likes_table_its_foreign_keys()
    {
        $this->createPostLikesWithoutKeys();

        $this->assertStringNotContainsString(
            'foreign key',
            strtolower($this->tableDefinition()),
            'The upgraded shape must start without the keys, or this test proves nothing.'
        );

        $this->db->table('users')->insert([['id' => 1], ['id' => 2]]);
        $this->db->table('posts')->insert([['id' => 1], ['id' => 2]]);
        $this->db->table('post_likes')->insert([
            ['post_id' => 1, 'user_id' => 1],
            ['post_id' => 1, 'user_id' => 2],
            ['post_id' => 2, 'user_id' => 1],
            // Orphans left behind while the keys did not exist: the keys
            // cannot be added to a table that still contains them.
            ['post_id' => 99, 'user_id' => 1],
            ['post_id' => 2, 'user_id' => 99],
        ]);

        $this->runMigration();

        $this->assertSame(2, substr_count(strtolower($this->tableDefinition()), 'foreign key'));
        $this->assertSame(0, $this->db->table('post_likes')->where('user_id', 99)->count());
        $this->assertSame(0, $this->db->table('post_likes')->where('post_id', 99)->count());

        // The row the deletion leaves behind is the bug: with the keys in
        // place, removing a post removes its likes.
        $this->db->table('posts')->where('id', 1)->delete();

        $this->assertSame(0, $this->db->table('post_likes')->where('post_id', 1)->count());
        $this->assertTrue($this->db->table('post_likes')->where('post_id', 2)->where('user_id', 1)->exists());

        // Running again must not append the keys a second time.
        $this->runMigration();
        $this->assertSame(2, substr_count(strtolower($this->tableDefinition()), 'foreign key'));
    }

    #[Test]
    public function the_migration_leaves_a_table_that_already_has_its_keys_alone()
    {
        $this->db->getSchemaBuilder()->create('post_likes', function (Blueprint $table) {
            $table->unsignedInteger('post_id');
            $table->unsignedInteger('user_id');
            $table->primary(['post_id', 'user_id']);
            $table->foreign('post_id')->references('id')->on('posts')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });

        $before = $this->tableDefinition();

        $this->runMigration();

        $this->assertSame($before, $this->tableDefinition());
    }

    private function createPostLikesWithoutKeys(): void
    {
        $this->db->getSchemaBuilder()->create('post_likes', function (Blueprint $table) {
            $table->unsignedInteger('post_id');
            $table->unsignedInteger('user_id');
            $table->primary(['post_id', 'user_id']);
        });
    }

    private function tableDefinition(): string
    {
        return (string) $this->db->selectOne(
            'SELECT sql FROM sqlite_master WHERE type = "table" AND name = ?',
            [$this->prefix.'post_likes']
        )->sql;
    }

    private function runMigration(): void
    {
        $migration = require __DIR__.'/../../../migrations/2026_11_22_000000_add_sqlite_post_likes_foreign_keys.php';
        $migration['up']($this->db->getSchemaBuilder());
    }
}
