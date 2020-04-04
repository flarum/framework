<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Discussion\Search\Gambit;

use Flarum\Discussion\Search\DiscussionSearch;
use Flarum\Post\Post;
use Flarum\Search\AbstractRegexGambit;
use Flarum\Search\AbstractSearch;
use Flarum\User\UserRepository;
use Illuminate\Database\Query\Expression;
use LogicException;

class ContributorGambit extends AbstractRegexGambit
{
    /**
     * {@inheritdoc}
     */
    protected $pattern = 'contributor:(.+)';

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
    protected function conditions(AbstractSearch $search, array $matches, $negate)
    {
        if (!$search instanceof DiscussionSearch) {
            throw new LogicException('This gambit can only be applied on a DiscussionSearch');
        }

        $usernames = trim($matches[1], '"');
        $usernames = explode(',', $usernames);

        $ids = [];
        foreach ($usernames as $username) {
            $ids[] = $this->users->getIdForUsername($username);
        }

        $query = $search->getQuery();
        $grammar = $query->getGrammar();

        $subquery = Post::whereVisibleTo($search->getActor())
            ->select('posts.discussion_id')
            ->whereIn('user_id', $ids, 'and', $negate);

        $query
            ->join(
                new Expression('(' . $subquery->toSql() . ') ' . $grammar->wrapTable('posts_ft')),
                'posts_ft.discussion_id',
                '=',
                'discussions.id'
            )
            ->addBinding($subquery->getBindings(), 'join');
    }
}
