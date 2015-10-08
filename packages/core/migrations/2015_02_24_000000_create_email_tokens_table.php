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

class CreateEmailTokensTable extends AbstractMigration
{
    public function up()
    {
        $this->schema->create('email_tokens', function (Blueprint $table) {
            $table->string('id', 100)->primary();
            $table->string('email', 150);
            $table->integer('user_id')->unsigned();
            $table->timestamp('created_at');
        });
    }

    public function down()
    {
        $this->schema->drop('email_tokens');
    }
}
