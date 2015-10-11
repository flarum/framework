<?php
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Mentions\Migration;

use Flarum\Database\AbstractMigration;
use Illuminate\Database\Schema\Blueprint;

class CreateMentionsUsersTable extends AbstractMigration
{
    public function up()
    {
        $this->schema->create('mentions_users', function (Blueprint $table) {
            $table->integer('post_id')->unsigned();
            $table->integer('mentions_id')->unsigned();
            $table->primary(['post_id', 'mentions_id']);
        });
    }

    public function down()
    {
        $this->schema->drop('mentions_users');
    }
}
