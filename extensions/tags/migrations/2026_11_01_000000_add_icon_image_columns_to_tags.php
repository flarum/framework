<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Builder;

return [
    'up' => function (Builder $schema) {
        $schema->table('tags', function (Blueprint $table) {
            $table->string('icon_image_path', 100)->nullable()->after('icon');
            $table->unsignedTinyInteger('icon_image_scales')->default(1)->after('icon_image_path');
        });
    },

    'down' => function (Builder $schema) {
        $schema->table('tags', function (Blueprint $table) {
            $table->dropColumn(['icon_image_path', 'icon_image_scales']);
        });
    }
];
