<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Messages;

use Flarum\Extension\ExtensionManager;
use Flarum\Messages\Dialog\Event\UserRead;
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\User\User;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\Grammars\PostgresGrammar;

/**
 * Who has read a member's own messages, from the other members' read
 * positions. Only members the viewer may see are counted, and receipts go
 * both ways: a member who doesn't share theirs sees nobody else's.
 */
class ReadReceipts
{
    public const PREFERENCE = 'discloseReadReceipts';

    /** Messages up to this one predate receipts, when mark-all moved every read position. */
    public const AFTER_SETTING = 'flarum-messages.read_receipts_after';

    /** Authors further back than this see a read on their next load instead. */
    public const MAX_SIGNALLED = 50;

    /** SQLite allows at most 500 SELECTs in a UNION. */
    protected const POSITIONS_PER_QUERY = 100;

    public function __construct(
        protected SettingsRepositoryInterface $settings,
        protected ExtensionManager $extensions
    ) {
    }

    public static function shares(User $user): bool
    {
        return (bool) $user->getPreference(self::PREFERENCE);
    }

    public function after(): int
    {
        return (int) $this->settings->get(self::AFTER_SETTING, 0);
    }

    /**
     * How many other members share receipts, and the latest of the actor's
     * messages each reader has read. Nothing here loads the whole audience,
     * which a recipient Group can make large.
     *
     * @return array{members: int|null, after: int, readers: list<array{userId: string, messageId: int}>}|null
     */
    public function for(Dialog $dialog, User $actor): ?array
    {
        if (! self::shares($actor)) {
            return null;
        }

        $after = $this->after();
        $others = fn () => $this->visibleMembers($dialog, $actor)->where('users.id', '!=', $actor->id);
        $grammar = $others()->getQuery()->getGrammar();
        $preferences = $grammar->wrap('users.preferences');

        // PostgreSQL stores them as json, which LIKE doesn't take.
        if ($grammar instanceof PostgresGrammar) {
            $preferences = "CAST($preferences AS TEXT)";
        }

        $first = $this->ownComments($dialog, $actor, $after)->min('id');

        if (! $first) {
            return ['members' => null, 'after' => $after, 'readers' => []];
        }

        // Preferences are JSON written by PHP; MySQL's JSON column reads it back with a space.
        $optedOut = "$preferences like ? or $preferences like ?";
        $counts = $others()->toBase()
            ->selectRaw('count(*) as members')
            ->selectRaw("sum(case when $optedOut then 1 else 0 end) as opted_out", ['%"'.self::PREFERENCE.'":false%', '%"'.self::PREFERENCE.'": false%'])
            ->first();

        $receipts = ['members' => (int) $counts->members - (int) $counts->opted_out, 'after' => $after, 'readers' => []];

        $positions = DialogMessage::query()->getConnection()->table('dialog_user')
            ->where('dialog_id', $dialog->id)
            ->where('user_id', '!=', $actor->id)
            ->where('last_read_message_id', '>=', $first)
            ->pluck('last_read_message_id', 'user_id')
            ->map(fn ($position) => (int) $position);

        if ($positions->isEmpty()) {
            return $receipts;
        }

        $readers = $this->visibleMembers($dialog, $actor, $positions->keys()->map(fn ($id) => (int) $id)->all())
            ->get(['users.id', 'users.preferences'])
            ->filter(fn (User $member) => self::shares($member));

        $latest = $this->latestUpTo($dialog, $actor, $after, $readers
            ->map(fn (User $member) => $positions[$member->id])
            ->unique()
            ->values()
            ->all());

        foreach ($readers as $member) {
            $messageId = $latest[$positions[$member->id]] ?? null;

            if ($messageId) {
                $receipts['readers'][] = ['userId' => (string) $member->id, 'messageId' => $messageId];
            }
        }

        return $receipts;
    }

    /**
     * The authors of the comments just read past, each with the latest of
     * theirs the reader now has, as `for()` would give it.
     *
     * @return array<int, array{dialogId: string, userId: string, messageId: int}> Keyed by author.
     */
    public function signals(UserRead $event): array
    {
        $state = $event->state;
        $reader = $state->user;
        $from = max($event->previousMessageId, $this->after());

        if (! $reader || ! $state->dialog || ! self::shares($reader) || $event->readMessageId <= $from) {
            return [];
        }

        $latest = [];

        DialogMessage::query()
            ->where('type', DialogMessage::COMMENT)
            ->where('dialog_id', $state->dialog_id)
            ->where('id', '>', $from)
            ->where('id', '<=', $event->readMessageId)
            ->where('user_id', '!=', $reader->id)
            ->orderByDesc('id')
            ->limit(self::MAX_SIGNALLED)
            ->get(['id', 'user_id'])
            ->each(function (DialogMessage $message) use (&$latest) {
                $latest[$message->user_id] ??= $message->id;
            });

        if (! $latest) {
            return [];
        }

        // Whole rows: permissions turn on more than groups (is_email_confirmed).
        $authors = DialogAudience::users($state->dialog, userIds: array_keys($latest))
            ->with('groups')
            ->get()
            ->filter(fn (User $author) => self::shares($author));

        // The reader may have left, or lost the Group they were in it through, while queued.
        if (! DialogAudience::users($state->dialog, userIds: [$reader->id])->exists()) {
            return [];
        }

        $readerOnlyInHiddenGroups = null;
        $payloads = [];

        foreach ($authors as $author) {
            if (! User::query()->whereKey($reader->id)->whereVisibleTo($author)->exists()) {
                continue;
            }

            if ($author->cannot('viewHiddenGroups')) {
                $readerOnlyInHiddenGroups ??= ! DialogAudience::users($state->dialog, hiddenGroups: false, userIds: [$reader->id])->exists();

                if ($readerOnlyInHiddenGroups) {
                    continue;
                }
            }

            $payloads[$author->id] = [
                'dialogId' => (string) $state->dialog_id,
                'userId' => (string) $reader->id,
                'messageId' => $latest[$author->id],
            ];
        }

        return $payloads;
    }

    /**
     * Members the actor may see, leaving out those only in a hidden Group
     * the actor may not see.
     *
     * @param int[]|null $userIds
     * @return EloquentBuilder<User>
     */
    protected function visibleMembers(Dialog $dialog, User $actor, ?array $userIds = null): EloquentBuilder
    {
        return DialogAudience::users($dialog, $actor->can('viewHiddenGroups'), $userIds)
            ->whereVisibleTo($actor)
            // Anonymising resets preferences to sharing, but nobody reads as them any more.
            ->when($this->extensions->isEnabled('flarum-gdpr'), fn ($query) => $query->where('users.anonymized', false));
    }

    protected function ownComments(Dialog $dialog, User $actor, int $after): Builder
    {
        return DialogMessage::query()->toBase()
            ->where('dialog_id', $dialog->id)
            ->where('user_id', $actor->id)
            ->where('type', DialogMessage::COMMENT)
            ->where('id', '>', $after);
    }

    /**
     * The actor's latest comment at or before each read position, keyed by
     * position. Each position is a constant in its own MAX() so the index
     * answers it: as an outer column, neither MySQL nor PostgreSQL uses it to
     * bound the index.
     *
     * @param int[] $positions
     * @return array<int, int|null>
     */
    protected function latestUpTo(Dialog $dialog, User $actor, int $after, array $positions): array
    {
        $latest = [];

        foreach (array_chunk($positions, self::POSITIONS_PER_QUERY) as $batch) {
            $query = null;

            foreach ($batch as $position) {
                $part = DialogMessage::query()->getConnection()->query()
                    ->selectRaw(((int) $position).' as read_position')
                    ->selectSub(fn (Builder $query) => $query->from('dialog_messages')
                        ->selectRaw('max(id)')
                        ->where('dialog_id', $dialog->id)
                        ->where('user_id', $actor->id)
                        ->where('type', DialogMessage::COMMENT)
                        ->where('id', '>', $after)
                        ->where('id', '<=', $position), 'read_up_to');

                $query = $query ? $query->unionAll($part) : $part;
            }

            foreach ($query->get() as $row) {
                $latest[(int) $row->read_position] = $row->read_up_to === null ? null : (int) $row->read_up_to;
            }
        }

        return $latest;
    }
}
