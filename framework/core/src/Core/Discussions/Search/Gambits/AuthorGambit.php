<?php namespace Flarum\Core\Discussions\Search\Gambits;

use Flarum\Core\Discussions\Search\DiscussionSearch;
use Flarum\Core\Users\UserRepositoryInterface;
use Flarum\Core\Search\RegexGambit;
use Flarum\Core\Search\Search;
use LogicException;

class AuthorGambit extends RegexGambit
{
    /**
     * {@inheritdoc}
     */
    protected $pattern = 'author:(.+)';

    /**
     * @var UserRepositoryInterface
     */
    protected $users;

    /**
     * @param UserRepositoryInterface $users
     */
    public function __construct(UserRepositoryInterface $users)
    {
        $this->users = $users;
    }

    /**
     * {@inheritdoc}
     */
    protected function conditions(Search $search, array $matches, $negate)
    {
        if (! $search instanceof DiscussionSearch) {
            throw new LogicException('This gambit can only be applied on a DiscussionSearch');
        }

        $username = trim($matches[1], '"');

        $id = $this->users->getIdForUsername($username);

        $search->getQuery()->where('start_user_id', $negate ? '!=' : '=', $id);
    }
}
