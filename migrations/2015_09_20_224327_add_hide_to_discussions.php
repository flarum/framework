<?php
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Core\Migration;

use Flarum\Database\AbstractMigration;
use Illuminate\Database\Schema\Blueprint;

class AddHideToDiscussions extends AbstractMigration
{
    public function up()
    {
        $this->schema->table('discussions', function (Blueprint $table) {
            $table->dateTime('hide_time')->nullable();
            $table->integer('hide_user_id')->unsigned()->nullable();
        });
    }

    public function down()
    {
        $this->schema->table('discussions', function (Blueprint $table) {
            $table->dropColumn(['hide_time', 'hide_user_id']);
        });
    }
}
