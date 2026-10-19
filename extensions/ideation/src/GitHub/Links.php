<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Ideation\GitHub;

use Carbon\Carbon;
use Flarum\Discussion\Discussion;
use Flarum\Foundation\DispatchEventsTrait;
use Flarum\Foundation\ValidationException;
use Flarum\Ideation\Event\StatusChanged;
use Flarum\Ideation\Status;
use Flarum\Locale\TranslatorInterface;
use Flarum\User\Guest;
use Flarum\User\User;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Collection;

/**
 * The one door to `ideation_github_links`: linking, unlinking, the
 * webhook, the sync and the automatic status moves all come through here.
 */
class Links
{
    use DispatchEventsTrait;

    public function __construct(
        protected Config $config,
        protected Client $client,
        protected Dispatcher $events,
        protected TranslatorInterface $translator
    ) {
    }

    /**
     * Resolve what a moderator pasted into an item GitHub vouches for.
     * Runs before the discussion is saved, so a bad link saves nothing.
     *
     * @return array{0: Reference, 1: array}
     */
    public function resolve(string $input): array
    {
        $ref = Reference::parse($input);

        if (! $ref) {
            $this->fail('not_a_link');
        }

        if (! $this->config->allows($ref->repo)) {
            $this->fail('repo_not_allowed', ['repo' => $ref->repo]);
        }

        try {
            $item = $this->client->fetch($ref);
        } catch (Unavailable) {
            $this->fail('unavailable');
        }

        if (! $item) {
            $this->fail('not_found', ['ref' => $input]);
        }

        return [$ref, $item];
    }

    public function link(Discussion $discussion, User $actor, Reference $ref, array $item): Link
    {
        /** @var Link $link */
        $link = Link::query()->firstOrNew([
            'discussion_id' => $discussion->id,
            'type' => $item['type'],
            'repo' => $ref->repo,
            'number' => $ref->number,
        ]);

        if (! $link->exists) {
            $link->user_id = $actor->id;
            $link->created_at = Carbon::now();
        }

        $this->fill($link, $item);
        $link->save();

        $this->settle($discussion, $link);

        return $link;
    }

    public function unlink(Discussion $discussion, int $linkId): void
    {
        Link::query()->where('discussion_id', $discussion->id)->whereKey($linkId)->delete();
    }

    /**
     * What GitHub told us, by webhook or sync, about one issue, pull request
     * or milestone: update every idea that links it, then let each one move
     * if the forum asked for that.
     */
    public function apply(string $repo, int $number, array $item): int
    {
        $types = $item['type'] === 'milestone' ? ['milestone'] : ['issue', 'pull'];

        $links = Link::query()
            ->where('repo', strtolower($repo))
            ->where('number', $number)
            ->whereIn('type', $types)
            ->get();

        foreach ($links as $link) {
            // Webhooks can arrive late, twice or out of order; an older
            // picture of the item never replaces a newer one.
            if ($item['updated_at'] && $link->source_updated_at && Carbon::parse($item['updated_at'])->lt($link->source_updated_at)) {
                continue;
            }

            $this->fill($link, $item);
            $link->save();
        }

        Discussion::query()
            ->whereIn('id', $links->pluck('discussion_id')->unique())
            ->get()
            ->each(fn (Discussion $d) => $this->settle($d, $links->firstWhere('discussion_id', $d->id)));

        return $links->count();
    }

    /**
     * Refresh the links GitHub has gone longest without confirming. Capped
     * per run, and a link GitHub could not answer for is still marked as
     * tried, so one bad repository never makes every run retry it.
     */
    public function sync(int $limit = 50): int
    {
        $done = 0;

        $stale = Link::query()
            ->where(fn ($q) => $q->whereNull('synced_at')->orWhere('synced_at', '<', Carbon::now()->subHours(6)))
            ->orderBy('synced_at')
            ->limit($limit)
            ->get();

        foreach ($stale as $link) {
            try {
                $item = $this->client->fetch(new Reference($link->repo, $link->type, $link->number));
            } catch (Unavailable) {
                // Rate limited or down: stop for this run instead of
                // spending the rest of the batch on the same answer.
                $link->synced_at = Carbon::now();
                $link->save();
                break;
            }

            if ($item) {
                $this->apply($link->repo, $link->number, $item);
            }

            Link::query()->whereKey($link->id)->update(['synced_at' => Carbon::now()]);
            $done++;
        }

        return $done;
    }

    /** @param Collection<int, Link>|null $links */
    public function linksOf(Discussion $discussion, ?Collection $links = null): Collection
    {
        return $links ?? Link::query()->where('discussion_id', $discussion->id)->get();
    }

    /**
     * The two optional rules, applied after anything about an idea's links
     * changes:
     *  - a linked pull request is open → In progress (from Under review or Planned)
     *  - every linked item that still counts is done → Shipped
     * Neither ever moves an idea backwards, and neither touches a closed one.
     */
    protected function settle(Discussion $discussion, ?Link $cause): void
    {
        if (! Status::isOpen($discussion->ideation_status) || ! ($this->config->autoShip() || $this->config->autoProgress())) {
            return;
        }

        $links = Link::query()->where('discussion_id', $discussion->id)->get();
        $counted = $links->map(fn (Link $l) => Item::done($l->type, $l->state))->filter(fn ($d) => $d !== null);

        $next = null;

        if ($this->config->autoShip() && $counted->isNotEmpty() && $counted->every(fn ($d) => $d === true)) {
            $next = Status::SHIPPED;
        } elseif ($this->config->autoProgress()
            && $discussion->ideation_status !== Status::IN_PROGRESS
            && $links->contains(fn (Link $l) => $l->type === 'pull' && $l->state === 'open')) {
            $next = Status::IN_PROGRESS;
        }

        if (! $next) {
            return;
        }

        $actor = $cause?->user_id ? (User::query()->find($cause->user_id) ?? new Guest()) : new Guest();

        $discussion->raise(new StatusChanged($discussion, $actor, $discussion->ideation_status, $next, null, $cause?->reference() ?? 'GitHub'));

        $discussion->ideation_status = $next;
        $discussion->ideation_status_changed_at = Carbon::now();
        $discussion->save();

        $this->dispatchEventsFor($discussion, $actor);
    }

    protected function fill(Link $link, array $item): void
    {
        $link->type = $item['type'];
        $link->title = $item['title'];
        $link->state = $item['state'];
        $link->open_issues = $item['open_issues'];
        $link->closed_issues = $item['closed_issues'];
        $link->source_updated_at = $item['updated_at'] ? Carbon::parse($item['updated_at']) : $link->source_updated_at;
        $link->synced_at = Carbon::now();
    }

    protected function fail(string $key, array $params = []): never
    {
        throw new ValidationException([
            'ideationGithubLink' => $this->translator->trans('flarum-ideation.api.github.'.$key, $params),
        ]);
    }
}
