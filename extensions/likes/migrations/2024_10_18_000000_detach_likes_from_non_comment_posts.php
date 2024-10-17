<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

use Illuminate\Database\Schema\Builder;

return [
    'up' => function (Builder $schema) {
        // Detach likes on non-comment posts
        $schema->getConnection()
            ->table('post_likes')
            ->whereNotExists(function ($query) {
                $query->selectRaw(1)->from('posts')->whereColumn('id', 'post_id')->where('type', 'comment');
            })
            ->delete();
    },
    'down' => function (Builder $schema) {
    }
];
