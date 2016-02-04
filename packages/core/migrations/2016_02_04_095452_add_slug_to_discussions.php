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

use Flarum\Core\Discussion;
use Flarum\Database\AbstractMigration;
use Flarum\Util\Str;
use Illuminate\Database\Schema\Blueprint;

class AddSlugToDiscussions extends AbstractMigration
{
    public function up()
    {
        $this->schema->table('discussions', function (Blueprint $table) {
            $table->string('slug');
        });

        // Store slugs for existing discussions
        Discussion::chunk(100, function ($discussions) {
            foreach ($discussions as $discussion) {
                $discussion->slug = Str::slug($discussion->title);
                $discussion->save();
            }
        });
    }

    public function down()
    {
        $this->schema->table('discussions', function (Blueprint $table) {
            $table->dropColumn('slug');
        });
    }
}
