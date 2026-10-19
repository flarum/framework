<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

use Flarum\Database\Migration;
use Illuminate\Database\Schema\Blueprint;

/*
 * An idea's GitHub issues, pull requests and milestones, with the state last
 * seen on GitHub. Pages read this table, never GitHub: it is kept current by
 * the webhook and a capped hourly sync.
 *
 * `type` is issue, pull or milestone. Issues and pull requests share one
 * number space per repository and milestones have their own, which is why the
 * webhook looks rows up by (repo, number) and type.
 */
return Migration::createTable('ideation_github_links', function (Blueprint $table) {
    $table->increments('id');
    $table->unsignedInteger('discussion_id');
    $table->unsignedInteger('user_id')->nullable();
    $table->string('type', 10);
    $table->string('repo', 140);
    $table->unsignedInteger('number');
    $table->string('title', 255)->default('');
    $table->string('state', 20)->default('open');
    $table->unsignedInteger('open_issues')->nullable();
    $table->unsignedInteger('closed_issues')->nullable();
    $table->dateTime('source_updated_at')->nullable();
    $table->dateTime('synced_at')->nullable();
    $table->dateTime('created_at')->nullable();

    $table->unique(['discussion_id', 'type', 'repo', 'number'], 'ideation_gh_link_unique');
    $table->index(['repo', 'number'], 'ideation_gh_repo_number');
    $table->index('synced_at', 'ideation_gh_synced');

    $table->foreign('discussion_id')->references('id')->on('discussions')->cascadeOnDelete();
    $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
});
