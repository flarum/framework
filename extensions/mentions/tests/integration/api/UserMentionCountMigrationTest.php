<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Mentions\Tests\integration\api;

use Flarum\Testing\integration\TestCase;
use Illuminate\Container\Container;
use Illuminate\Database\Connectors\ConnectionFactory;
use Illuminate\Database\Schema\Blueprint;
use PHPUnit\Framework\Attributes\Test;

class UserMentionCountMigrationTest extends TestCase
{
    #[Test]
    public function the_migration_backfills_and_rolls_back_on_an_isolated_connection()
    {
        $this->extension('flarum-mentions');

        // DDL must never commit the application's fixture transaction. Use a
        // separate connection and private tables, including on MySQL/MariaDB.
        $config = $this->database()->getConfig();
        $config['prefix'] = 'count_'.bin2hex(random_bytes(4)).'_';
        if ($config['driver'] === 'sqlite') {
            $config['database'] = ':memory:';
        }
        $db = (new ConnectionFactory(new Container))->make($config);
        $schema = $db->getSchemaBuilder();

        try {
            $schema->create('users', function (Blueprint $table) {
                $table->increments('id');
            });
            $schema->create('posts', function (Blueprint $table) {
                $table->increments('id');
                $table->boolean('is_private')->default(false);
                $table->string('type')->default('comment');
                $table->timestamp('hidden_at')->nullable();
            });
            $schema->create('post_mentions_user', function (Blueprint $table) {
                $table->unsignedInteger('post_id');
                $table->unsignedInteger('mentions_user_id');
                $table->primary(['post_id', 'mentions_user_id']);
                $table->foreign('post_id')->references('id')->on('posts')->cascadeOnDelete();
                $table->foreign('mentions_user_id')->references('id')->on('users')->cascadeOnDelete();
            });
            $db->table('users')->insert([['id' => 1], ['id' => 2]]);
            $db->table('posts')->insert([
                ['id' => 1, 'is_private' => false, 'type' => 'comment', 'hidden_at' => null],
                ['id' => 2, 'is_private' => true, 'type' => 'comment', 'hidden_at' => null],
                ['id' => 3, 'is_private' => false, 'type' => 'comment', 'hidden_at' => '2026-01-01 00:00:00'],
                ['id' => 4, 'is_private' => false, 'type' => 'discussionRenamed', 'hidden_at' => null],
            ]);
            foreach ([1, 2, 3, 4] as $postId) {
                $db->table('post_mentions_user')->insert(['post_id' => $postId, 'mentions_user_id' => 1]);
            }
            $migration = require __DIR__.'/../../../migrations/2026_10_13_000000_add_mention_count_to_users_table.php';

            $migration['up']($schema);

            // Exercise repeated down/up, not only a fresh installation.
            for ($i = 0; $i < 2; $i++) {
                $migration['down']($schema);
                $this->assertFalse($schema->hasColumn('users', 'mention_count'));
                $migration['up']($schema);
                $this->assertSame(1, (int) $db->table('users')->where('id', 1)->value('mention_count'));
                $this->assertSame(0, (int) $db->table('users')->where('id', 2)->value('mention_count'));
            }
        } finally {
            // Even a failed assertion between down/up leaves no changed schema
            // or committed rows in the application's test tables.
            $schema->dropIfExists('post_mentions_user');
            $schema->dropIfExists('posts');
            $schema->dropIfExists('users');
            $db->disconnect();
        }
    }

    #[Test]
    public function the_revision_migration_starts_existing_users_at_zero_and_rolls_back()
    {
        $this->extension('flarum-mentions');

        $config = $this->database()->getConfig();
        $config['prefix'] = 'rev_'.bin2hex(random_bytes(4)).'_';
        if ($config['driver'] === 'sqlite') {
            $config['database'] = ':memory:';
        }
        $db = (new ConnectionFactory(new Container))->make($config);
        $schema = $db->getSchemaBuilder();

        try {
            $schema->create('users', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('mention_count')->default(0);
            });
            $db->table('users')->insert([['id' => 1, 'mention_count' => 3]]);
            $migration = require __DIR__.'/../../../migrations/2026_11_27_000000_add_mention_count_revision_to_users_table.php';

            $migration['up']($schema);

            for ($i = 0; $i < 2; $i++) {
                $this->assertSame(0, (int) $db->table('users')->where('id', 1)->value('mention_count_revision'));
                $this->assertSame(3, (int) $db->table('users')->where('id', 1)->value('mention_count'), 'Adding the revision must leave the count alone.');
                $migration['down']($schema);
                $this->assertFalse($schema->hasColumn('users', 'mention_count_revision'));
                $this->assertTrue($schema->hasColumn('users', 'mention_count'));
                $migration['up']($schema);
            }
        } finally {
            $schema->dropIfExists('users');
            $db->disconnect();
        }
    }
}
