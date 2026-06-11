<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Audit\Search\Gambits;

use Flarum\Search\AbstractRegexGambit;
use Flarum\Search\SearchState;
use Flarum\User\UserRepository;

class UserGambit extends AbstractRegexGambit
{
    protected $users;

    public function __construct(UserRepository $users)
    {
        $this->users = $users;
    }

    protected function getGambitPattern(): string
    {
        return 'user:(.+)';
    }

    protected function conditions(SearchState $search, array $matches, $negate)
    {
        $usernames = explode(',', trim($matches[1], '"'));

        $ids = [];
        foreach ($usernames as $username) {
            $ids[] = $this->users->getIdForUsername($username);
        }

        $search->getQuery()->whereIn($search->getQuery()->raw('json_extract(payload, "$.user_id")'), $ids, 'and', $negate);
    }
}
