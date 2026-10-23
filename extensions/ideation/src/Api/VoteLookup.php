<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Ideation\Api;

use Closure;
use Flarum\Api\Context;
use Flarum\Discussion\Discussion;
use Flarum\Ideation\Vote;
use Psr\Http\Message\ServerRequestInterface;
use stdClass;
use WeakMap;

/**
 * "Has the person looking voted for this?" for every idea in a response, in
 * ONE query.
 *
 * The getter returns a Closure, and the serializer resolves closures only
 * after every other getter has run, so by the time the first one runs every
 * idea on the page has queued its id. The first closure loads them all.
 * Guests and responses without ideas cost nothing at all, which an endpoint
 * eager load (one query on every discussion list, ideas or not) could not do.
 *
 * Memoised per request in a WeakMap, so a long-running worker never carries
 * one request's answers into the next.
 */
class VoteLookup
{
    /** @var WeakMap<ServerRequestInterface, stdClass>|null */
    protected static ?WeakMap $requests = null;

    public static function voted(Discussion $discussion, Context $context): bool|Closure
    {
        $actor = $context->getActor();

        if ($actor->isGuest()) {
            return false;
        }

        self::$requests ??= new WeakMap();
        $state = self::$requests[$context->request] ??= (object) ['pending' => [], 'known' => []];
        $state->pending[$discussion->id] = true;

        return function () use ($state, $discussion, $actor): bool {
            if (! array_key_exists($discussion->id, $state->known)) {
                $ids = array_keys(array_diff_key($state->pending, $state->known));

                $state->known += array_fill_keys($ids, false);

                foreach (Vote::query()->where('user_id', $actor->id)->whereIn('discussion_id', $ids)->pluck('discussion_id') as $id) {
                    $state->known[(int) $id] = true;
                }
            }

            return $state->known[$discussion->id];
        };
    }
}
