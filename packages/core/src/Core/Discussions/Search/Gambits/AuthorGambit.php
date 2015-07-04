<?php namespace Flarum\Core\Discussions\Search\Gambits;

use Flarum\Core\Discussions\Search\DiscussionSearch;
use Flarum\Core\Users\UserRepository;
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
