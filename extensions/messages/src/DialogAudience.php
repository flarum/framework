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

    /**
     * @param bool $hiddenGroups Whether members of hidden recipient Groups count.
     * @param int[]|null $userIds Only these users; applied inside the union so
     *                            a large recipient Group isn't read in full.
     * @return Builder<User>
     */
    public static function users(Dialog $dialog, bool $hiddenGroups = true, ?array $userIds = null): Builder
    {
        $connection = $dialog->getConnection();
        $individual = $connection->table('dialog_user')->select('user_id')
            ->where('dialog_id', $dialog->id)->where('is_recipient', true)
            ->when($userIds !== null, fn ($query) => $query->whereIntegerInRaw('user_id', $userIds));
        $groups = $connection->table('group_user')->select('group_user.user_id')
            ->join('dialog_group', 'dialog_group.group_id', '=', 'group_user.group_id')
            ->where('dialog_group.dialog_id', $dialog->id)
            ->when(! $hiddenGroups, fn ($query) => $query->join('groups', 'groups.id', '=', 'dialog_group.group_id')->where('groups.is_hidden', false))
            ->when($userIds !== null, fn ($query) => $query->whereIntegerInRaw('group_user.user_id', $userIds));

        // PostgreSQL estimates NOT IN as keeping half of all users, and seq-scans them.
        return User::query()->whereIn('users.id', $connection->query()->fromSub($individual->union($groups), 'user_audience')->select('user_audience.user_id'))
            ->whereNotExists(fn ($query) => $query->selectRaw('1')->from('dialog_user as left_members')
                ->whereColumn('left_members.user_id', 'users.id')
                ->where('left_members.dialog_id', $dialog->id)
                ->whereNotNull('left_members.left_at'));
    }
}
