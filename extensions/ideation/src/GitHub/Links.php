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
use Flarum\Ideation\Service\IdeaLock;
use Flarum\Ideation\Status;
use Flarum\Locale\TranslatorInterface;
use Flarum\User\Guest;
use Flarum\User\User;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Contracts\Events\Dispatcher;

/**
 * The one door to `ideation_github_links`: linking, unlinking, the
 * webhook, the sync and the automatic status moves all come through here.
 */
class Links
{
    use DispatchEventsTrait;

    /** GitHub lookups one moderator may make in a minute, linking. */
    public const LOOKUPS_PER_MINUTE = 10;

    /** How long GitHub's answer to a lookup, "no such item" included, is reused (seconds). */
    public const LOOKUP_TTL = 60;

    public function __construct(
        protected Config $config,
        protected Client $client,
        protected Dispatcher $events,
        protected TranslatorInterface $translator,
        protected Cache $cache,
        protected IdeaLock $lock
    ) {
    }

    /**
     * Resolve what a moderator pasted into an item GitHub vouches for.
     * Runs before the discussion is saved, so a bad link saves nothing.
     *
     * The forum's token (or its server's address, without one) has one
     * GitHub budget for everyone, the sync included. So an answer is reused
     * for a minute, a missing item as much as a found one, and each
     * moderator may ask GitHub only so often.
     *
     * @return array{0: Reference, 1: array}
     */
    public function resolve(string $input, User $actor): array
    {
        $ref = Reference::parse($input);

        if (! $ref) {
            $this->fail('not_a_link');
        }

        if (! $this->config->allows($ref->repo)) {
            $this->fail('repo_not_allowed', ['repo' => $ref->repo]);
        }

        $key = 'flarum-ideation.github.lookup.'.sha1($ref->repo.'/'.($ref->type === 'milestone' ? 'milestone' : 'issue').'/'.$ref->number);
        $cached = $this->cache->get($key);

        if (is_array($cached) && array_key_exists('item', $cached)) {
            $item = $cached['item'];
        } else {
            $this->throttle($actor);

            try {
                $item = $this->client->fetch($ref);
            } catch (Unavailable) {
                $this->fail('unavailable');
            } catch (Gone) {
                $this->fail('moved', ['ref' => $input]);
            }

            $this->cache->put($key, ['item' => $item], self::LOOKUP_TTL);
        }

        if (! $item) {
            $this->fail('not_found', ['ref' => $input]);
        }

        return [$ref, $item];
    }

    /** Counts a lookup against the moderator's minute, refusing it once the minute is used up. */
    protected function throttle(User $actor): void
    {
        $key = 'flarum-ideation.github.lookups.'.$actor->id;

        // add() only writes when the key is absent, so the minute starts
        // once and increment() never resets it.
        $this->cache->add($key, 0, 60);

        if ((int) $this->cache->increment($key) > self::LOOKUPS_PER_MINUTE) {
            $this->fail('too_many_lookups');
        }
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

        // Linked again with an answer from the lookup cache that is older
        // than what a webhook has brought since: keep the newer picture.
        if (! $link->exists || ! $item['updated_at'] || ! $link->source_updated_at || ! Carbon::parse($item['updated_at'])->lt($link->source_updated_at)) {
            $this->fill($link, $item);
            $link->save();
        }

        $this->settle($discussion, $link);

        return $link;
    }

    public function unlink(Discussion $discussion, User $actor, int $linkId): void
    {
        if (Link::query()->where('discussion_id', $discussion->id)->whereKey($linkId)->delete()) {
            // Removing the last open item can leave everything else done.
            // The moderator's click caused that move, so it is theirs.
            $this->settle($discussion, null, $actor);
        }
    }

    /**
     * What GitHub told us, by webhook or sync, about one issue, pull request
     * or milestone: update every idea that links it, then let each one move
     * if the forum asked for that.
     */
    public function apply(string $repo, int $number, array $item): int
    {
        // A repository taken off the list is no longer followed, by webhook
        // or by sync, and its links never move an idea.
        if (! $this->config->allows($repo)) {
            return 0;
        }

        $types = $item['type'] === 'milestone' ? ['milestone'] : ['issue', 'pull'];

        $links = Link::query()
            ->where('repo', strtolower($repo))
            ->where('number', $number)
            ->whereIn('type', $types)
            ->get();

        $values = $this->values($item);
        $updatedAt = $values['source_updated_at'] ?? null;

        foreach ($links as $link) {
            // Webhooks can arrive late, twice or out of order; an older
            // picture of the item never replaces a newer one. The check is
            // part of the UPDATE, so two deliveries at once cannot both pass
            // it and then save in the wrong order.
            $updated = Link::query()
                ->whereKey($link->id)
                ->when($updatedAt, fn ($query) => $query->where(
                    fn ($query) => $query->whereNull('source_updated_at')->orWhere('source_updated_at', '<=', $updatedAt)
                ))
                ->update($values);

            if ($updated) {
                $this->fill($link, $item);
                $link->syncOriginal();
            }
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

        // Only repositories still on the list: the token is never spent on
        // one that was taken off it. Few distinct repositories, so this is
        // a short list, and it keeps unlisted links out of the cap.
        $repos = Link::query()->distinct()->pluck('repo')->filter(fn (string $repo) => $this->config->allows($repo));

        $stale = Link::query()
            ->whereIn('repo', $repos->all())
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
            } catch (Gone) {
                // This one item moved (a renamed or transferred repository)
                // or is blocked. Marked as tried, so it waits its turn again,
                // and the rest of the batch goes on.
                Link::query()->whereKey($link->id)->update(['synced_at' => Carbon::now()]);
                continue;
            }

            if ($item) {
                $this->apply($link->repo, $link->number, $item);
            }

            Link::query()->whereKey($link->id)->update(['synced_at' => Carbon::now()]);
            $done++;
        }

        return $done;
    }

    /**
     * The two optional rules, applied after anything about an idea's links
     * changes:
     *  - a linked pull request is open → In progress (from Under review or Planned)
     *  - every linked item that still counts is done → Shipped
     * Neither ever moves an idea backwards, and neither touches a closed one.
     *
     * With `$by`, a moderator's own action (removing a link) caused the move,
     * so it is recorded as their manual move: the post names them rather
     * than GitHub, and they are not told about their own ship.
     */
    protected function settle(Discussion $discussion, ?Link $cause, ?User $by = null): void
    {
        if (! $this->config->autoShip() && ! $this->config->autoProgress()) {
            return;
        }

        $links = Link::query()
            ->where('discussion_id', $discussion->id)
            ->get()
            ->filter(fn (Link $l) => $this->config->allows($l->repo));
        $counted = $links->map(fn (Link $l) => Item::done($l->type, $l->state))->filter(fn ($d) => $d !== null);
        $now = Carbon::now();

        // Decided and written under the idea's lock, from its status read
        // there: a vote on its way lands before the move or is refused, and
        // an idea a moderator closed or merged a moment ago is left alone.
        [$old, $next] = $discussion->getConnection()->transaction(function () use ($discussion, $links, $counted, $now) {
            $old = $this->lock->status($discussion->id);
            $next = null;

            if (! Status::isOpen($old)) {
                return [$old, null];
            }

            if ($this->config->autoShip() && $counted->isNotEmpty() && $counted->every(fn ($d) => $d === true)) {
                $next = Status::SHIPPED;
            } elseif ($this->config->autoProgress()
                && $old !== Status::IN_PROGRESS
                && $links->contains(fn (Link $l) => $l->type === 'pull' && $l->state === 'open')) {
                $next = Status::IN_PROGRESS;
            }

            if ($next) {
                Discussion::query()->whereKey($discussion->id)->update(['ideation_status' => $next, 'ideation_status_changed_at' => $now]);
            }

            return [$old, $next];
        });

        if (! $next) {
            return;
        }

        $actor = $by ?? ($cause?->user_id ? (User::query()->find($cause->user_id) ?? new Guest()) : new Guest());
        $via = $by ? null : ($cause?->reference() ?? 'GitHub');

        $discussion->ideation_status = $next;
        $discussion->ideation_status_changed_at = $now;
        $discussion->syncOriginalAttributes(['ideation_status', 'ideation_status_changed_at']);

        $discussion->raise(new StatusChanged($discussion, $actor, $old, $next, null, $via));
        $this->dispatchEventsFor($discussion, $actor);
    }

    protected function fill(Link $link, array $item): void
    {
        $link->forceFill($this->values($item));
    }

    /** The columns GitHub's picture of an item sets. */
    protected function values(array $item): array
    {
        $values = [
            'type' => $item['type'],
            'title' => $item['title'],
            'state' => $item['state'],
            'open_issues' => $item['open_issues'],
            'closed_issues' => $item['closed_issues'],
            'synced_at' => Carbon::now(),
        ];

        // An answer without a time keeps the time already known.
        if ($item['updated_at']) {
            $values['source_updated_at'] = Carbon::parse($item['updated_at']);
        }

        return $values;
    }

    protected function fail(string $key, array $params = []): never
    {
        throw new ValidationException([
            'ideationGithubLink' => $this->translator->trans('flarum-ideation.api.github.'.$key, $params),
        ]);
    }
}
