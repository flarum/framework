<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Builder;
use Illuminate\Support\Str;

return [
    'up' => function (Builder $schema) {
        $schema->table('discussions', function (Blueprint $table) {
            $table->string('slug');
        });

        // Store slugs for existing discussions
        $schema->getConnection()->table('discussions')->chunkById(100, function ($discussions) use ($schema) {
            foreach ($discussions as $discussion) {
                $schema->getConnection()->table('discussions')->where('id', $discussion->id)->update([
                    'slug' => Str::slug($discussion->title)
                ]);
            }
        });
    },

    'down' => function (Builder $schema) {
        $schema->table('discussions', function (Blueprint $table) {
            $table->dropColumn('slug');
        });
    }
];
