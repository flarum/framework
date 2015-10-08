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

class CreateConfigTable extends AbstractMigration
{
    public function up()
    {
        $this->schema->create('config', function (Blueprint $table) {
            $table->string('key', 100)->primary();
            $table->binary('value')->nullable();
        });
    }

    public function down()
    {
        $this->schema->drop('config');
    }
}
