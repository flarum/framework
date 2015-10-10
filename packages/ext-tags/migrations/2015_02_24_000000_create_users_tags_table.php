<?php
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Tags\Migration;

use Flarum\Database\AbstractMigration;
use Illuminate\Database\Schema\Blueprint;

class CreateUsersTagsTable extends AbstractMigration
{
    public function up()
    {
        $this->schema->create('users_tags', function (Blueprint $table) {
            $table->integer('user_id')->unsigned();
            $table->integer('tag_id')->unsigned();
            $table->dateTime('read_time')->nullable();
            $table->boolean('is_hidden')->default(0);
            $table->primary(['user_id', 'tag_id']);
        });
    }

    public function down()
    {
        $this->schema->drop('users_tags');
    }
}
