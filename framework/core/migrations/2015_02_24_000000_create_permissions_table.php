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

class CreatePermissionsTable extends AbstractMigration
{
    public function up()
    {
        $this->schema->create('permissions', function (Blueprint $table) {
            $table->integer('group_id')->unsigned();
            $table->string('permission', 100);
            $table->primary(['group_id', 'permission']);
        });
    }

    public function down()
    {
        $this->schema->drop('permissions');
    }
}
