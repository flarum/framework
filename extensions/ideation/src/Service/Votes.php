<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Ideation\Service;

use Carbon\Carbon;
use Flarum\Discussion\Discussion;
use Flarum\Ideation\Vote;
use Flarum\User\User;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Query\Builder;

/**
 * 🚨 The one door to `ideation_votes`. Every change to a vote row changes the
 * idea's count in the same transaction and by the number of rows actually
 * written, so the count cannot drift from the table: a repeated vote inserts
 * nothing and adds nothing, a repeated unvote deletes nothing and takes
 * nothing away.
 */
class Votes
{
    public function __construct(
        protected ConnectionInterface $db
    ) {
    }

    public function set(Discussion $discussion, User $user, bool $voted): void
    {
        $this->db->transaction(function () use ($discussion, $user, $voted) {
            if ($voted) {
                $changed = Vote::query()->insertOrIgnore([
                    'discussion_id' => $discussion->id,
                    'user_id' => $user->id,
                    'created_at' => Carbon::now(),
                ]);
            } else {
                $changed = Vote::query()
                    ->where('discussion_id', $discussion->id)
                    ->where('user_id', $user->id)
                    ->delete();
            }

            if ($changed > 0) {
                $this->adjust($discussion->id, $voted ? $changed : -$changed);
            }
        });

        $discussion->ideation_votes = $this->count($discussion->id);
        $discussion->syncOriginalAttribute('ideation_votes');
    }

    /**
     * Carry every vote on $source over to $target. A member who voted for
     * both keeps one vote, on the target: the merge never counts a person
     * twice. Returns how many votes the target gained.
     */
    public function move(Discussion $source, Discussion $target): int
    {
        $moved = $this->db->transaction(function () use ($source, $target) {
            $moved = Vote::query()->insertUsing(
                ['discussion_id', 'user_id', 'created_at'],
                $this->db->table('ideation_votes', 'source')
                    // An integer literal, not a bound parameter: PostgreSQL
                    // types a bare parameter in a SELECT list as text and
                    // refuses to insert it into an integer column.
                    ->selectRaw((int) $target->id.' as discussion_id')
                    ->addSelect('source.user_id', 'source.created_at')
                    ->where('source.discussion_id', $source->id)
                    ->whereNotExists(function (Builder $query) use ($target) {
                        $query->selectRaw('1')
                            ->from('ideation_votes', 'existing')
                            ->where('existing.discussion_id', $target->id)
                            ->whereColumn('existing.user_id', 'source.user_id');
                    })
            );

            $removed = Vote::query()->where('discussion_id', $source->id)->delete();

            $this->adjust($target->id, $moved);
            $this->adjust($source->id, -$removed);

            return $moved;
        });

        foreach ([$source, $target] as $discussion) {
            $discussion->ideation_votes = $this->count($discussion->id);
            $discussion->syncOriginalAttribute('ideation_votes');
        }

        return $moved;
    }

    /**
     * Before a user row is deleted (the foreign key then removes their
     * votes), take their vote off every idea they voted for. One statement
     * however many ideas that is.
     */
    public function forgetUser(User $user): void
    {
        Discussion::query()
            ->whereIn('id', Vote::query()->select('discussion_id')->where('user_id', $user->id))
            ->where('ideation_votes', '>', 0)
            ->decrement('ideation_votes');
    }

    protected function adjust(int $discussionId, int $by): void
    {
        if ($by === 0) {
            return;
        }

        $query = Discussion::query()->whereKey($discussionId);

        $by > 0
            ? $query->increment('ideation_votes', $by)
            : $query->where('ideation_votes', '>=', -$by)->decrement('ideation_votes', -$by);
    }

    protected function count(int $discussionId): int
    {
        return (int) Discussion::query()->whereKey($discussionId)->value('ideation_votes');
    }
}
