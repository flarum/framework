<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Audit\Search\Filter;

use Flarum\Search\Database\DatabaseSearchState;
use Flarum\Search\Filter\FilterInterface;
use Flarum\Search\SearchState;
use Flarum\Search\ValidateFilterTrait;
use Flarum\User\UserRepository;

/**
 * @implements FilterInterface<DatabaseSearchState>
 */
class ActorFilter implements FilterInterface
{
    use ValidateFilterTrait;

    public function __construct(
        protected UserRepository $users
    ) {
    }

    public function getFilterKey(): string
    {
        return 'actor';
    }

    public function filter(SearchState $state, string|array $value, bool $negate): void
    {
        $raw = $this->asString($value);

        if ($raw === 'guest') {
            $state->getQuery()->whereNull('actor_id', 'and', $negate);

            return;
        }

        $ids = array_map(
            fn (string $username) => $this->users->getIdForUsername($username),
            explode(',', $raw)
        );

        $state->getQuery()->whereIn('actor_id', $ids, 'and', $negate);
    }
}
