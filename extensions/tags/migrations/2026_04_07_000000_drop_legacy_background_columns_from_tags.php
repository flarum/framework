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
        if (! $schema->hasTable('tags')) {
            return;
        }

        if ($schema->hasColumn('tags', 'background_path')) {
            $schema->table('tags', function (Blueprint $table) {
                $table->dropColumn('background_path');
            });
        }

        if ($schema->hasColumn('tags', 'background_mode')) {
            $schema->table('tags', function (Blueprint $table) {
                $table->dropColumn('background_mode');
            });
        }
    },
    'down' => function (Builder $schema) {
        if (! $schema->hasTable('tags')) {
            return;
        }

        if (! $schema->hasColumn('tags', 'background_path')) {
            $schema->table('tags', function (Blueprint $table) {
                $table->string('background_path', 100)->nullable()->after('color');
            });
        }

        if (! $schema->hasColumn('tags', 'background_mode')) {
            $afterColumn = $schema->hasColumn('tags', 'background_path') ? 'background_path' : 'color';

            $schema->table('tags', function (Blueprint $table) use ($afterColumn) {
                $table->string('background_mode', 100)->nullable()->after($afterColumn);
            });
        }
    }
];
