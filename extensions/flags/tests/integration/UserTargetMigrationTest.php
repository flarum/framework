<?php

namespace Flarum\Flags\Tests\integration;

use Flarum\Group\Group;
use Illuminate\Database\Capsule\Manager;
use Illuminate\Database\Schema\Blueprint;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class UserTargetMigrationTest extends TestCase
{
    #[Test]
    public function upgrade_preserves_post_reports_and_deletion_cascades_then_downgrade_removes_account_only_rows(): void
    {
        $capsule = new Manager();
        $capsule->addConnection(['driver' => 'sqlite', 'database' => ':memory:', 'foreign_key_constraints' => true]);
        $db = $capsule->getConnection();
        $schema = $db->getSchemaBuilder();
        $schema->create('groups', fn (Blueprint $table) => $table->increments('id'));
        $schema->create('group_permission', function (Blueprint $table) {
            $table->unsignedInteger('group_id');
            $table->string('permission');
        });
        $schema->create('users', fn (Blueprint $table) => $table->increments('id'));
        $schema->create('posts', fn (Blueprint $table) => $table->increments('id'));
        $schema->create('flags', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('post_id');
            $table->foreign('post_id')->references('id')->on('posts')->onDelete('cascade');
        });
        $db->table('groups')->insert([['id' => Group::MEMBER_ID], ['id' => Group::MODERATOR_ID]]);
        $db->table('users')->insert([['id' => 1], ['id' => 2]]);
        $db->table('posts')->insert(['id' => 1]);
        $db->table('flags')->insert(['id' => 1, 'post_id' => 1]);
        $migration = require __DIR__.'/../../migrations/2026_10_02_000000_add_user_targets.php';
        $migration['up']($schema);
        $this->assertSame(1, $db->table('flags')->where('post_id', 1)->count());
        $db->table('flags')->insert([
            ['id' => 2, 'post_id' => null, 'target_user_id' => 1],
            ['id' => 3, 'post_id' => null, 'target_user_id' => 2],
        ]);
        $db->table('users')->where('id', 1)->delete();
        $this->assertSame(0, $db->table('flags')->where('target_user_id', 1)->count());
        $this->assertSame(1, $db->table('flags')->where('target_user_id', 2)->count());
        $migration['down']($schema);
        $this->assertFalse($schema->hasColumn('flags', 'target_user_id'));
        $this->assertSame(1, $db->table('flags')->count());
        $this->assertSame(1, $db->table('flags')->first()->post_id);
        $this->assertFalse(collect($schema->getColumns('flags'))->firstWhere('name', 'post_id')['nullable']);
        $this->assertSame(0, $db->table('group_permission')->whereIn('permission', ['user.flag', 'user.viewFlags'])->count());
    }
}
