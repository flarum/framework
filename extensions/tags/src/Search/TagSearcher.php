<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tags\Search;

use Flarum\Search\AbstractSearcher;
use Flarum\Search\FilterManager;
use Flarum\Tags\Tag;
use Flarum\Tags\TagRepository;
use Flarum\User\User;
use Illuminate\Database\Eloquent\Builder;

class TagSearcher extends AbstractSearcher
{
    protected function getQuery(User $actor): Builder
    {
        return Tag::whereVisibleTo($actor)->select('tags.*');
    }
}
