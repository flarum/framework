<?php
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Akismet\Migration;

use Flarum\Database\AbstractMigration;
use Illuminate\Database\Schema\Blueprint;

class AddIsSpamToPosts extends AbstractMigration
{
    public function up()
    {
        $this->schema->table('posts', function (Blueprint $table) {
            $table->boolean('is_spam')->default(0);
        });
    }

    public function down()
    {
        $this->schema->table('posts', function (Blueprint $table) {
            $table->dropColumn('is_spam');
        });
    }
}
