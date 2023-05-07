<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Database\Eloquent;

use Illuminate\Database\Eloquent\Collection as BaseCollection;

class Collection extends BaseCollection
{
    /**
     * This is done to prevent conflicts when using visibility scopes.
     * Without this, we get the following example query when using a visibility scope
     * and eager loading the count of `mentionedBy`:.
     *
     * ```sql
     * SELECT `id`, (
     *   SELECT count(*)
     *   FROM `posts` AS `laravel_reserved_0`
     *   INNER JOIN `post_mentions_post` ON `laravel_reserved_0`.`id` = `post_mentions_post`.`post_id`
     *   WHERE `posts`.`id` = `post_mentions_post`.`mentions_post_id`
     *   ---   ^^^^^^^ this is the problem, visibility scopes always assume the default table name, rather than
     *   ---           the Laravel auto-generated alias.
     *
     *     AND `TYPE` in ('discussionTagged', 'discussionStickied', 'discussionLocked', 'comment', 'discussionRenamed')
     * ) AS `mentioned_by_count`
     * FROM `posts`
     * WHERE `posts`.`id` in (23642)
     * ```
     *
     * So by applying an alias on the parent query, we prevent Laravel from auto aliasing the sub-query.
     *
     * @link https://github.com/flarum/framework/pull/3780
     */
    public function loadAggregate($relations, $column, $function = null)
    {
        if ($this->isEmpty()) {
            return $this;
        }

        return $this->first()->withTableAlias(function () use ($relations, $column, $function) {
            return parent::loadAggregate($relations, $column, $function);
        });
    }
}
