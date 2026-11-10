<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Messages;

use Flarum\User\User;
use Illuminate\Database\Eloquent\Builder;

class DialogAudience
{
    /** @param Builder<Dialog> $query */
    public static function scope(Builder $query, int $userId): void
    {
        $connection = $query->getConnection();
        $individual = $connection->table('dialog_user')->select('dialog_id')
            ->where('user_id', $userId)->where('is_recipient', true);
        $groups = $connection->table('dialog_group')->select('dialog_group.dialog_id')
            ->join('group_user', 'group_user.group_id', '=', 'dialog_group.group_id')
            ->where('group_user.user_id', $userId);

        $query->whereIn('dialogs.id', $connection->query()->fromSub($individual->union($groups), 'dialog_audience')->select('dialog_audience.dialog_id'))
            ->whereNotIn('dialogs.id', $connection->table('dialog_user')->select('dialog_id')
                ->where('user_id', $userId)->whereNotNull('left_at'));
    }

    /** @return Builder<User> */
    public static function users(Dialog $dialog): Builder
    {
        $connection = $dialog->getConnection();
        $individual = $connection->table('dialog_user')->select('user_id')
            ->where('dialog_id', $dialog->id)->where('is_recipient', true);
        $groups = $connection->table('group_user')->select('group_user.user_id')
            ->join('dialog_group', 'dialog_group.group_id', '=', 'group_user.group_id')
            ->where('dialog_group.dialog_id', $dialog->id);

        return User::query()->whereIn('users.id', $connection->query()->fromSub($individual->union($groups), 'user_audience')->select('user_audience.user_id'))
            ->whereNotIn('users.id', $connection->table('dialog_user')->select('user_id')
                ->where('dialog_id', $dialog->id)->whereNotNull('left_at'));
    }
}
