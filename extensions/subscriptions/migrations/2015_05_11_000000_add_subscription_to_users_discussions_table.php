<?php
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Subscriptions\Migration;

use Flarum\Database\AbstractMigration;
use Illuminate\Database\Schema\Blueprint;

class AddSubscriptionToUsersDiscussionsTable extends AbstractMigration
{
    public function up()
    {
        $this->schema->table('users_discussions', function (Blueprint $table) {
            $table->enum('subscription', ['follow', 'ignore'])->nullable();
        });
    }

    public function down()
    {
        $this->schema->table('users_discussions', function (Blueprint $table) {
            $table->dropColumn('subscription');
        });
    }
}
