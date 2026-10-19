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
use Flarum\Foundation\ValidationException;
use Flarum\Ideation\Event\StatusChanged;
use Flarum\Ideation\GitHub\Config as GitHubConfig;
use Flarum\Ideation\GitHub\Links;
use Flarum\Ideation\Service\Votes;
use Flarum\Ideation\Status;
use Flarum\Locale\TranslatorInterface;
use Illuminate\Contracts\Events\Dispatcher;

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
    public function __construct(
        protected Votes $votes,
        protected Dispatcher $events,
        protected TranslatorInterface $translator,
        protected GitHubConfig $github,
        protected Links $links
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
            Schema\Str::make('ideationStatus')
                ->nullable()
                ->visible($isIdea)
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

            Schema\DateTime::make('ideationStatusChangedAt')
                ->visible($isIdea)
                ->get(fn (Discussion $d) => $d->ideation_status_changed_at),

            Schema\Integer::make('ideationMergedIntoId')
                ->nullable()
                ->visible(fn (Discussion $d) => $d->ideation_merged_into_id !== null)
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
                    [$ref, $item] = $this->links->resolve($value);
                    $actor = $context->getActor();
                    $d->afterSave(fn () => $this->links->link($d, $actor, $ref, $item));
                }),
            Schema\Integer::make('ideationGithubUnlink')
                ->visible(false)
                ->writable($canLink)
                ->set(function (Discussion $d, int $value) {
                    $d->afterSave(fn () => $this->links->unlink($d, $value));
                }),
        ];
    }

    protected function setStatus(Discussion $d, ?string $value, Context $context): void
    {
        // Posting an idea always starts it under review; only a moderator
        // decides where it goes from there.
        if ($context->creating()) {
            $d->ideation_status = $value === null ? null : Status::UNDER_REVIEW;

            if ($value !== null) {
                $d->afterSave(fn () => $this->votes->set($d, $context->getActor(), true));
            }

            return;
        }

        if ($value !== null && ! in_array($value, Status::SETTABLE, true)) {
            throw new ValidationException(['ideationStatus' => $this->translator->trans('flarum-ideation.api.unknown_status')]);
        }

        $old = $d->ideation_status;

        if ($old === $value) {
            return;
        }

        $d->ideation_status = $value;
        $d->ideation_status_changed_at = Carbon::now();

        // Taking an idea back out of a merge un-merges it; its votes stay
        // with the idea they were carried to.
        if ($old === Status::MERGED) {
            $d->ideation_merged_into_id = null;
        }

        $actor = $context->getActor();
        $d->afterSave(fn () => $this->events->dispatch(new StatusChanged($d, $actor, $old, $value)));
    }

    protected function merge(Discussion $source, ?int $targetId, Context $context): void
    {
        $actor = $context->getActor();

        /** @var Discussion|null $target */
        $target = $targetId === null ? null : Discussion::query()
            ->whereVisibleTo($actor)
            ->whereNotNull('ideation_status')
            ->where('ideation_status', '!=', Status::MERGED)
            ->find($targetId);

        if ($source->ideation_status === null
            || $source->ideation_status === Status::MERGED
            || ! $target
            || $target->id === $source->id) {
            throw new ValidationException(['ideationMergedIntoId' => $this->translator->trans('flarum-ideation.api.merge_target')]);
        }

        $old = $source->ideation_status;

        $source->ideation_status = Status::MERGED;
        $source->ideation_merged_into_id = $target->id;
        $source->ideation_status_changed_at = Carbon::now();

        $source->afterSave(function () use ($source, $target, $actor, $old) {
            // Ideas already folded into this one follow it to the target, so
            // nothing ever points at a merged idea.
            Discussion::query()
                ->where('ideation_merged_into_id', $source->id)
                ->update(['ideation_merged_into_id' => $target->id]);

            $this->votes->move($source, $target);

            $this->events->dispatch(new StatusChanged($source, $actor, $old, Status::MERGED, $target));
        });
    }
}
