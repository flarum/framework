<?php
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Likes\Migration;

use Flarum\Database\AbstractMigration;
use Illuminate\Database\Schema\Blueprint;

class CreatePostsLikesTable extends AbstractMigration
{
    public function up()
    {
        $this->schema->create('posts_likes', function (Blueprint $table) {
            $table->integer('post_id')->unsigned();
            $table->integer('user_id')->unsigned();
            $table->primary(['post_id', 'user_id']);
        });
    }

    public function down()
    {
        $this->schema->drop('posts_likes');
    }
}
