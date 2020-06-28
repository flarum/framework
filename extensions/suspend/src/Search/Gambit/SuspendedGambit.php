<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Suspend\Search\Gambit;

use Carbon\Carbon;
use Flarum\Search\AbstractRegexGambit;
use Flarum\Search\AbstractSearch;
use Flarum\User\Search\UserSearch;
use Flarum\User\UserRepository;
use LogicException;

class SuspendedGambit extends AbstractRegexGambit
{
    /**
     * {@inheritdoc}
     */
    protected $pattern = 'is:suspended';

    /**
     * @var \Flarum\User\UserRepository
     */
    protected $users;

    /**
     * @param \Flarum\User\UserRepository $users
     */
    public function __construct(UserRepository $users)
    {
        $this->users = $users;
    }

    /**
     * {@inheritdoc}
     */
    public function apply(AbstractSearch $search, $bit)
    {
        if (! $search->getActor()->can('suspend')) {
            return false;
        }

        return parent::apply($search, $bit);
    }

    /**
     * {@inheritdoc}
     */
    protected function conditions(AbstractSearch $search, array $matches, $negate)
    {
        if (! $search instanceof UserSearch) {
            throw new LogicException('This gambit can only be applied on a DiscussionSearch');
        }

        $search->getQuery()->where(function ($query) use ($negate) {
            if ($negate) {
                $query->where('suspended_until', null)->orWhere('suspended_until', '<', Carbon::now());
            } else {
                $query->where('suspended_until', '>', Carbon::now());
            }
        });
    }
}
