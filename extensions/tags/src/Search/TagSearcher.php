<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tags\Search;

use Flarum\Search\AbstractSearcher;
use Flarum\Search\GambitManager;
use Flarum\Tags\TagRepository;
use Flarum\User\User;
use Illuminate\Database\Eloquent\Builder;

class TagSearcher extends AbstractSearcher
{
    /**
     * @var TagRepository
     */
    protected $tags;

    public function __construct(TagRepository $tags, GambitManager $gambits, array $searchMutators)
    {
        parent::__construct($gambits, $searchMutators);

        $this->tags = $tags;
    }

    protected function getQuery(User $actor): Builder
    {
        return $this->tags->query()->whereVisibleTo($actor);
    }
}
