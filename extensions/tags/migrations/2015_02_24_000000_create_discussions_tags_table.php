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

class CreateDiscussionsTagsTable extends AbstractMigration
{
    public function up()
    {
        $this->schema->create('discussions_tags', function (Blueprint $table) {
            $table->integer('discussion_id')->unsigned();
            $table->integer('tag_id')->unsigned();
            $table->primary(['discussion_id', 'tag_id']);
        });
    }

    public function down()
    {
        $this->schema->drop('discussions_tags');
    }
}
