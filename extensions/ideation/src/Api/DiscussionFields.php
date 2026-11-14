<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Ideation\Api;

use Carbon\Carbon;
use Flarum\Api\Context;
use Flarum\Api\Schema;
use Flarum\Discussion\Discussion;
use Flarum\Discussion\Event\Saving;
use Flarum\Foundation\ValidationException;
use Flarum\Ideation\Event\StatusChanged;
use Flarum\Ideation\GitHub\Config as GitHubConfig;
use Flarum\Ideation\GitHub\Links;
use Flarum\Ideation\Service\IdeaLock;
use Flarum\Ideation\Service\Votes;
use Flarum\Ideation\Status;
use Flarum\Locale\TranslatorInterface;
use Illuminate\Support\Arr;
use WeakMap;

/**
 * Everything Ideation adds to a discussion.
 *
 * Reads cost no queries except `ideationVoted` and `ideationGithub` (one
 * each per response, see VoteLookup and LinkLookup). Every write is an ordinary PATCH of the discussion, so it gets
 * the discussion's own visibility check for free: nobody can vote on, move or
 * merge an idea they cannot see.
 */
class DiscussionFields
{
    /** @var WeakMap<Discussion, int>|null merges set on a discussion, waiting for its save */
    protected static ?WeakMap $pendingMerges = null;

    public function __construct(
        protected Votes $votes,
        protected TranslatorInterface $translator,
        protected GitHubConfig $github,
        protected Links $links,
        protected IdeaLock $lock
    ) {
    }

    public function __invoke(): array
    {
        $isIdea = fn (Discussion $d) => $d->ideation_status !== null;
        $canLink = fn (Discussion $d, Context $context) => $context->updating()
            && $this->github->enabled()
            && $d->ideation_status !== null
            && $context->getActor()->can('ideationModerate', $d);

        return [
            // Always sent, null for a discussion that is not an idea: the store
            // keeps an attribute a response leaves out, so a reader would go
            // on seeing an idea someone else took off the roadmap.
            Schema\Str::make('ideationStatus')
                ->nullable()
                ->get(fn (Discussion $d) => $d->ideation_status)
                ->writable(function (Discussion $d, Context $context) {
                    return $context->creating()
                        ? $context->getActor()->hasPermission('ideation.postIdeas')
                        : $context->getActor()->can('ideationModerate', $d);
                })
                ->set(fn (Discussion $d, ?string $value, Context $context) => $this->setStatus($d, $value, $context)),

            Schema\Integer::make('ideationVotes')
                ->visible($isIdea)
                ->get(fn (Discussion $d) => (int) $d->ideation_votes),

            Schema\Boolean::make('ideationVoted')
                ->visible($isIdea)
                ->get(fn (Discussion $d, Context $context) => VoteLookup::voted($d, $context))
                ->writable(fn (Discussion $d, Context $context) => $context->updating() && $context->getActor()->can('ideationVote', $d))
                ->set(function (Discussion $d, bool $value, Context $context) {
                    $d->afterSave(fn () => $this->votes->set($d, $context->getActor(), $value));
                }),

            Schema\Boolean::make('canIdeationVote')
                ->visible($isIdea)
                ->get(fn (Discussion $d, Context $context) => $context->getActor()->can('ideationVote', $d)),

            // Per discussion, because a moderator's permission may be scoped
            // to the discussion's tags.
            Schema\Boolean::make('canIdeationModerate')
                ->get(fn (Discussion $d, Context $context) => $context->getActor()->can('ideationModerate', $d)),

            Schema\DateTime::make('ideationStatusChangedAt')
                ->visible($isIdea)
                ->get(fn (Discussion $d) => $d->ideation_status_changed_at),

            // Always sent too, for the same reason: an idea taken back out of a
            // merge clears it.
            Schema\Integer::make('ideationMergedIntoId')
                ->nullable()
                ->get(fn (Discussion $d) => $d->ideation_merged_into_id)
                ->writable(fn (Discussion $d, Context $context) => $context->updating() && $context->getActor()->can('ideationModerate', $d))
                ->set(fn (Discussion $d, ?int $value, Context $context) => $this->merge($d, $value, $context)),

            // The idea's GitHub issues, pull requests and milestones, as last
            // seen on GitHub. Only while the integration is on.
            Schema\Arr::make('ideationGithub')
                ->visible(fn (Discussion $d) => $d->ideation_status !== null && $this->github->enabled())
                ->get(fn (Discussion $d, Context $context) => LinkLookup::links($d, $context)),

            // Write-only: a GitHub URL or owner/repo#123 to link, and a link
            // id to remove. GitHub is asked before anything is saved.
            Schema\Str::make('ideationGithubLink')
                ->visible(false)
                ->writable($canLink)
                ->maxLength(300)
                ->set(function (Discussion $d, string $value, Context $context) {
                    $actor = $context->getActor();
                    [$ref, $item] = $this->links->resolve($value, $actor);
                    $d->afterSave(fn () => $this->links->link($d, $actor, $ref, $item));
                }),
            Schema\Integer::make('ideationGithubUnlink')
                ->visible(false)
                ->writable($canLink)
                ->set(function (Discussion $d, int $value, Context $context) {
                    $actor = $context->getActor();
                    $d->afterSave(fn () => $this->links->unlink($d, $actor, $value));
                }),
        ];
    }

    protected function setStatus(Discussion $d, ?string $value, Context $context): void
    {
        // Posting an idea always starts it under review; only a moderator
        // decides where it goes from there. The author's own vote is cast
        // once the discussion and its tags are saved (VoteForOwnIdea).
        if ($context->creating()) {
            $d->ideation_status = $value === null ? null : Status::UNDER_REVIEW;

            return;
        }

        if ($value !== null && ! in_array($value, Status::SETTABLE, true)) {
            throw new ValidationException(['ideationStatus' => $this->translator->trans('flarum-ideation.api.unknown_status')]);
        }

        $actor = $context->getActor();

        // Written after the save, under the idea's lock and from the status
        // read there: a vote on its way lands wholly before the move or is
        // refused after it, and the event post says what the idea really
        // moved from, even if someone else moved it a moment ago.
        $d->afterSave(function () use ($d, $value, $actor) {
            $now = Carbon::now();

            $old = $d->getConnection()->transaction(function () use ($d, $value, $now) {
                $old = $this->lock->status($d->id);

                if ($old !== $value) {
                    Discussion::query()->whereKey($d->id)->update(array_merge(
                        ['ideation_status' => $value, 'ideation_status_changed_at' => $now],
                        // Taking an idea back out of a merge un-merges it;
                        // its votes stay with the idea they were carried to.
                        $old === Status::MERGED ? ['ideation_merged_into_id' => null] : []
                    ));
                }

                return $old;
            });

            if ($old === $value) {
                return;
            }

            $d->ideation_status = $value;
            $d->ideation_status_changed_at = $now;

            if ($old === Status::MERGED) {
                $d->ideation_merged_into_id = null;
            }

            $d->syncOriginalAttributes(['ideation_status', 'ideation_status_changed_at', 'ideation_merged_into_id']);
            $d->raise(new StatusChanged($d, $actor, $old, $value));
        });
    }

    protected function merge(Discussion $source, ?int $targetId, Context $context): void
    {
        $actor = $context->getActor();

        // Only into an open idea: a shipped or declined idea's count is
        // frozen, and the voters moved onto a shipped one would never be told.
        /** @var Discussion|null $target */
        $target = $targetId === null ? null : Discussion::query()
            ->whereVisibleTo($actor)
            ->whereIn('ideation_status', Status::OPEN)
            ->find($targetId);

        if ($source->ideation_status === null
            || $source->ideation_status === Status::MERGED
            || ! $target
            || $target->id === $source->id) {
            $this->refuseMerge();
        }

        // The merge changes the target's count and who hears when it ships,
        // so moderating the source is not enough.
        $actor->assertCan('ideationModerate', $target);

        // A merge sets the status itself; a second status in the same
        // request would leave the idea neither merged nor moved.
        if (array_key_exists('ideationStatus', (array) Arr::get($context->body(), 'data.attributes'))) {
            $this->refuseMerge();
        }

        // Checked again with both ideas locked just before the discussion is
        // saved (lockPendingMerge, on core's Saving event): after every field
        // of the request is set, so a GitHub link in the same request is
        // looked up before any lock is taken, and before the source's own
        // row is written, so the locks are taken in IdeaLock's order. A PATCH
        // that merges runs in one transaction (MergeInOneTransaction), so the
        // locks hold until it ends: another merge of either idea, a ship or a
        // decline waits for this one, and a refusal leaves the request's
        // other changes unsaved too.
        self::$pendingMerges ??= new WeakMap();
        self::$pendingMerges[$source] = $target->id;

        // The merge itself is written after the save, under the same locks:
        // the source is read again there, so of two merges of one idea into
        // two others the second is refused instead of pointing the idea at
        // one target while its votes sit on the other.
        $source->afterSave(function () use ($source, $target, $actor) {
            $now = Carbon::now();

            $old = $source->getConnection()->transaction(function () use ($source, $target, $now) {
                $old = $this->lockForMerge($source->id, $target->id);

                Discussion::query()->whereKey($source->id)->update([
                    'ideation_status' => Status::MERGED,
                    'ideation_merged_into_id' => $target->id,
                    'ideation_status_changed_at' => $now,
                ]);

                // Ideas already folded into this one follow it to the target,
                // so nothing ever points at a merged idea.
                Discussion::query()
                    ->where('ideation_merged_into_id', $source->id)
                    ->update(['ideation_merged_into_id' => $target->id]);

                $this->votes->move($source, $target);

                return $old;
            });

            $source->ideation_status = Status::MERGED;
            $source->ideation_merged_into_id = $target->id;
            $source->ideation_status_changed_at = $now;
            $source->syncOriginalAttributes(['ideation_status', 'ideation_merged_into_id', 'ideation_status_changed_at']);

            // Dispatched once the discussion is saved, after the votes have moved.
            $source->raise(new StatusChanged($source, $actor, $old, Status::MERGED, $target));
        });
    }

    /** Core's Saving event: take a pending merge's locks and check it, before the save. */
    public function lockPendingMerge(Saving $event): void
    {
        $targetId = self::$pendingMerges[$event->discussion] ?? null;

        if ($targetId !== null) {
            unset(self::$pendingMerges[$event->discussion]);
            $this->lockForMerge($event->discussion->id, $targetId);
        }
    }

    /**
     * Lock the source, the target and the ideas already merged into the
     * source (IdeaLock's order), and refuse the merge
     * unless the source is still an unmerged idea and the target still open.
     * Returns the source's status.
     */
    protected function lockForMerge(int $sourceId, int $targetId): string
    {
        // The ideas already folded into the source are rewritten too, so
        // they are locked in the same statement, in the same order.
        $followers = Discussion::query()->where('ideation_merged_into_id', $sourceId)->pluck('id')->map(intval(...))->all();
        $ideas = $this->lock->lock($sourceId, $targetId, ...$followers);
        $from = $ideas->get($sourceId)?->ideation_status;

        if ($from === null || $from === Status::MERGED || ! Status::isOpen($ideas->get($targetId)?->ideation_status)) {
            $this->refuseMerge();
        }

        return $from;
    }

    protected function refuseMerge(): never
    {
        throw new ValidationException(['ideationMergedIntoId' => $this->translator->trans('flarum-ideation.api.merge_target')]);
    }
}
