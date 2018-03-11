<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Illuminate\Support\Str;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Builder;

return [
    'up' => function (Builder $schema) {
        $schema->table('discussions', function (Blueprint $table) {
            $table->string('slug');
        });

        $app = Application::getInstance();
        $locale = $app->make(LocaleManager::class)->getLocale() ?? 'en';

        // Store slugs for existing discussions
        $schema->getConnection()->table('discussions')->chunkById(100, function ($discussions) use ($schema, $locale) {
            foreach ($discussions as $discussion) {
                $schema->getConnection()->table('discussions')->where('id', $discussion->id)->update([
                    'slug' => Str::slug($discussion->title, '-', $locale)
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
