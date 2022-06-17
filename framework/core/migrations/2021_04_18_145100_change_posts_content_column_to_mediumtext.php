<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Builder;

// https://github.com/doctrine/dbal/issues/2566#issuecomment-480217999
return [
    'up' => static function (Builder $schema) {
        $schema->table('posts', static function (Blueprint $table) {
            $table->mediumText('content')->comment(' ')->change();
        });
    },

    'down' => static function (Builder $schema) {
        $schema->table('posts', static function (Blueprint $table) {
            $table->text('content')->comment('')->change();
        });
    }
];
