<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Messages\Search;

use Flarum\Messages\DialogMessage;
use Flarum\Search\Database\AbstractSearcher;
use Flarum\User\User;
use Illuminate\Database\Eloquent\Builder;

class DialogMessageSearcher extends AbstractSearcher
{
    public function getQuery(User $actor): Builder
    {
        return DialogMessage::whereVisibleTo($actor);
    }
}
