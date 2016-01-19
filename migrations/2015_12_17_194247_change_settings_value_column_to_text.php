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

class ChangeSettingsValueColumnToText extends AbstractMigration
{
    public function up()
    {
        $this->schema->table('settings', function (Blueprint $table) {
            $table->text('value')->change();
        });
    }

    public function down()
    {
        $this->schema->table('settings', function (Blueprint $table) {
            $table->binary('value')->change();
        });
    }
}
