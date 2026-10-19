<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Ideation\Tests\integration;

use Flarum\Ideation\GitHub\Client;
use Flarum\Ideation\GitHub\Reference;
use Flarum\Ideation\GitHub\Unavailable;

/** GitHub, as the tests want it to answer. Records every request. */
class FakeGitHub implements Client
{
    /** @var array<string, array|null|'down'> keyed "repo#number" or "repo/milestone/number" */
    public array $items = [];

    /** @var string[] */
    public array $asked = [];

    public function fetch(Reference $ref): ?array
    {
        $key = $ref->type === 'milestone' ? "$ref->repo/milestone/$ref->number" : "$ref->repo#$ref->number";
        $this->asked[] = $key;
        $item = $this->items[$key] ?? null;

        if ($item === 'down') {
            throw new Unavailable('down');
        }

        return $item;
    }
}
