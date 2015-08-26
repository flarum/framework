<?php
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Core\Users\Search\Gambits;

use Flarum\Core\Users\UserRepository;
use Flarum\Core\Search\Search;
use Flarum\Core\Search\Gambit;

class FulltextGambit implements Gambit
{
    /**
     * @var UserRepository
     */
    protected $users;

    /**
     * @param UserRepository $users
     */
    public function __construct(UserRepository $users)
    {
        $this->users = $users;
    }

    /**
     * {@inheritdoc}
     */
    public function apply(Search $search, $bit)
    {
        $users = $this->users->getIdsForUsername($bit, $search->getActor());

        $search->getQuery()->whereIn('id', $users);

        $search->setDefaultSort(['id' => $users]);
    }
}
