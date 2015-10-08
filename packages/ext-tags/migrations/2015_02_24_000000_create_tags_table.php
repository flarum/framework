<?php
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Tags\Migrations;

use Flarum\Database\AbstractMigration;
use Flarum\Tags\Tag;
use Illuminate\Database\Schema\Blueprint;

class CreateTagsTable extends AbstractMigration
{
    public function up()
    {
        $this->schema->create('tags', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name', 100);
            $table->string('slug', 100);
            $table->text('description')->nullable();

            $table->string('color', 50)->nullable();
            $table->string('background_path', 100)->nullable();
            $table->string('background_mode', 100)->nullable();

            $table->integer('position')->nullable();
            $table->integer('parent_id')->unsigned()->nullable();
            $table->string('default_sort', 50)->nullable();
            $table->boolean('is_restricted')->default(0);
            $table->boolean('is_hidden')->default(0);

            $table->integer('discussions_count')->unsigned()->default(0);
            $table->dateTime('last_time')->nullable();
            $table->integer('last_discussion_id')->unsigned()->nullable();
        });

        Tag::unguard();
        Tag::insert([
            'name' => 'General',
            'slug' => 'general',
            'color' => '#888',
            'position' => '0'
        ]);
    }

    public function down()
    {
        $this->schema->drop('tags');
    }
}
