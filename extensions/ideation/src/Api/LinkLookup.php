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
use Flarum\Ideation\GitHub\Config;
use Flarum\Ideation\GitHub\Link;
use Psr\Http\Message\ServerRequestInterface;
use stdClass;
use WeakMap;

/**
 * Every idea's GitHub links for a whole response, in ONE query, the same way
 * VoteLookup answers "have I voted?". Nothing at all while the integration is
 * off or the page has no ideas.
 */
class LinkLookup
{
    /** @var WeakMap<ServerRequestInterface, stdClass>|null */
    protected static ?WeakMap $requests = null;

    public static function links(Discussion $discussion, Context $context): Closure
    {
        self::$requests ??= new WeakMap();
        $state = self::$requests[$context->request] ??= (object) ['pending' => [], 'known' => []];
        $state->pending[$discussion->id] = true;

        return function () use ($state, $discussion): array {
            if (! array_key_exists($discussion->id, $state->known)) {
                $config = resolve(Config::class);
                $ids = array_keys(array_diff_key($state->pending, $state->known));
                $state->known += array_fill_keys($ids, []);

                $rows = Link::query()->whereIn('discussion_id', $ids)->orderBy('type')->orderBy('id')->get();

                foreach ($rows as $link) {
                    // A repository taken off the list stops showing too.
                    if (! $config->allows($link->repo)) {
                        continue;
                    }

                    $state->known[$link->discussion_id][] = [
                        'id' => $link->id,
                        'type' => $link->type,
                        'repo' => $link->repo,
                        'number' => $link->number,
                        'title' => $link->title,
                        'state' => $link->state,
                        'openIssues' => $link->open_issues,
                        'closedIssues' => $link->closed_issues,
                        'url' => $link->url(),
                    ];
                }
            }

            return $state->known[$discussion->id];
        };
    }
}
