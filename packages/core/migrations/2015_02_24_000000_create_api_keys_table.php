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

class CreateApiKeysTable extends AbstractMigration
{
    public function up()
    {
        $this->schema->create('api_keys', function (Blueprint $table) {
            $table->string('id', 100)->primary();
        });
    }

    public function down()
    {
        $this->schema->drop('api_keys');
    }
}
