<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Mentions\Filter;

use Flarum\Search\Database\DatabaseSearchState;
use Flarum\Search\Filter\FilterInterface;
use Flarum\Search\SearchState;
use Flarum\Search\ValidateFilterTrait;
use Flarum\User\UserRepository;

/**
 * @implements FilterInterface<DatabaseSearchState>
 */
class MentionedFilter implements FilterInterface
{
    use ValidateFilterTrait;

    public function __construct(
        protected UserRepository $users
    ) {
    }

    public function getFilterKey(): string
    {
        return 'mentioned';
    }

    public function filter(SearchState $state, string|array $value, bool $negate): void
    {
        $mentionedUsername = $this->asString($value);

        $mentionedId = $this->users->getIdForUsername($mentionedUsername);

        if (! $mentionedId) {
            $mentionedId = intval($mentionedUsername);
        }

        $state
            ->getQuery()
            ->join('post_mentions_user', 'posts.id', '=', 'post_mentions_user.post_id')
            ->where('post_mentions_user.mentions_user_id', $negate ? '!=' : '=', $mentionedId);
    }
}
