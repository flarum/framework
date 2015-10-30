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

class AddIpAddressToPosts extends AbstractMigration
{
    public function up()
    {
        $this->schema->table('posts', function (Blueprint $table) {
            $table->string('ip_address', 45)->nullable();
        });
    }

    public function down()
    {
        $this->schema->table('posts', function (Blueprint $table) {
            $table->dropColumn(['ip_address']);
        });
    }
}
