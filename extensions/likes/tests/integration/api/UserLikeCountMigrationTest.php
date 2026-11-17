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
use Illuminate\Database\Connectors\ConnectionFactory;
use Illuminate\Database\Schema\Blueprint;
use PHPUnit\Framework\Attributes\Test;

class UserLikeCountMigrationTest extends TestCase
{
    #[Test]
    public function the_migration_backfills_and_rolls_back_on_an_isolated_connection()
    {
        $this->extension('flarum-likes');

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
            $schema->create('post_likes', function (Blueprint $table) {
                $table->unsignedInteger('post_id');
                $table->unsignedInteger('user_id');
                $table->primary(['post_id', 'user_id']);
                $table->foreign('post_id')->references('id')->on('posts')->cascadeOnDelete();
                $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            });
            $db->table('users')->insert([['id' => 1], ['id' => 2]]);
            $db->table('posts')->insert([
                ['id' => 1, 'is_private' => false, 'type' => 'comment', 'hidden_at' => null],
                ['id' => 2, 'is_private' => true, 'type' => 'comment', 'hidden_at' => null],
                ['id' => 3, 'is_private' => false, 'type' => 'comment', 'hidden_at' => '2026-01-01 00:00:00'],
                ['id' => 4, 'is_private' => false, 'type' => 'discussionRenamed', 'hidden_at' => null],
            ]);
            foreach ([1, 2, 3, 4] as $postId) {
                $db->table('post_likes')->insert(['post_id' => $postId, 'user_id' => 1]);
            }
            $migration = require __DIR__.'/../../../migrations/2026_10_13_000000_add_like_count_to_users_table.php';

            $migration['up']($schema);

            // Exercise repeated down/up, not only a fresh installation.
            for ($i = 0; $i < 2; $i++) {
                $migration['down']($schema);
                $this->assertFalse($schema->hasColumn('users', 'like_count'));
                $migration['up']($schema);
                $this->assertSame(2, (int) $db->table('users')->where('id', 1)->value('like_count'));
                $this->assertSame(0, (int) $db->table('users')->where('id', 2)->value('like_count'));
            }
        } finally {
            // Even a failed assertion between down/up leaves no changed schema
            // or committed rows in the application's test tables.
            $schema->dropIfExists('post_likes');
            $schema->dropIfExists('posts');
            $schema->dropIfExists('users');
            $db->disconnect();
        }
    }
}
