<?php
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Suspend\Migration;

use Flarum\Database\AbstractMigration;
use Illuminate\Database\Schema\Blueprint;

class RenameSuspendedUntilColumn extends AbstractMigration
{
    public function up()
    {
        $this->schema->table('users', function (Blueprint $table) {
            $table->renameColumn('suspended_until', 'suspend_until');
        });
    }

    public function down()
    {
        $this->schema->table('users', function (Blueprint $table) {
            $table->renameColumn('suspend_until', 'suspended_until');
        });
    }
}
