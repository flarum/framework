<?php
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Lock\Migration;

use Flarum\Database\AbstractMigration;
use Illuminate\Database\Schema\Blueprint;

class AddLockedToDiscussions extends AbstractMigration
{
    public function up()
    {
        $this->schema->table('discussions', function (Blueprint $table) {
            $table->boolean('is_locked')->default(0);
        });
    }

    public function down()
    {
        $this->schema->table('discussions', function (Blueprint $table) {
            $table->dropColumn('is_locked');
        });
    }
}
