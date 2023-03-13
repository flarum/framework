<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

use Flarum\Database\Migration;
use Illuminate\Database\Schema\Blueprint;

/**
 * Why does this migration reside here rather than in the mentions extension?
 *
 * To be able to use a foreign key constraint on the `mentions_tag_id` column,
 * we need to create the `post_mentions_tag` table after the `tags` table has
 * been created. This is not possible in the mentions extension, because the
 * tags extension is not always enabled.
 *
 * Other solutions such as conditional migrations have more implications and
 * require more changes to the process of enabling/disabling extensions.
 * @link https://github.com/flarum/framework/pull/3689
 *
 * This is a temporary solution until we implement mentionable models in v2.0.
 * @link https://github.com/orgs/flarum/projects/22/views/1?pane=issue&itemId=21752599
 *
 * At the same time, because of https://github.com/flarum/issue-archive/issues/44,
 * we need a way to track which tags are referenced in a tag change event post,
 * so this might actually be a permanent solution.
 */
return Migration::createTable(
    'post_mentions_tag',
    function (Blueprint $table) {
        $table->unsignedInteger('post_id');
        $table->foreign('post_id')
            ->references('id')
            ->on('posts')
            ->cascadeOnDelete();
        $table->unsignedInteger('mentions_tag_id');
        $table->foreign('mentions_tag_id')
            ->references('id')
            ->on('tags')
            ->cascadeOnDelete();
        $table->dateTime('created_at')->useCurrent()->nullable();

        $table->primary(['post_id', 'mentions_tag_id']);
    }
);
