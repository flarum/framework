<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\User\Search\Gambit;

use Flarum\Search\AbstractRegexGambit;
use Flarum\Search\AbstractSearch;
use Flarum\User\Search\UserSearch;
use Flarum\User\UserRepository;
use LogicException;

class EmailGambit extends AbstractRegexGambit
{
    /**
     * {@inheritdoc}
     */
    protected $pattern = 'email:(.+)';

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
        if (! $search->getActor()->hasPermission('user.edit')) {
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
            throw new LogicException('This gambit can only be applied on a UserSearch');
        }

        $email = trim($matches[1], '"');

        $search->getQuery()->where('email', $negate ? '!=' : '=', $email);
    }
}
