<?php 
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Tags\Listeners;

use Flarum\Events\RegisterDiscussionGambits;
use Flarum\Events\DiscussionSearchWillBePerformed;
use Illuminate\Contracts\Events\Dispatcher;
use Flarum\Tags\Gambits\TagGambit;
use Flarum\Tags\Tag;
use Illuminate\Database\Query\Expression;

class AddTagGambit
{
    public function subscribe(Dispatcher $events)
    {
        $events->listen(RegisterDiscussionGambits::class, [$this, 'registerTagGambit']);
        $events->listen(DiscussionSearchWillBePerformed::class, [$this, 'hideTags']);
    }

    public function registerTagGambit(RegisterDiscussionGambits $event)
    {
        $event->gambits->add('Flarum\Tags\Gambits\TagGambit');
    }

    public function hideTags(DiscussionSearchWillBePerformed $event)
    {
        $query = $event->search->getQuery();

        foreach ($event->search->getActiveGambits() as $gambit) {
            if ($gambit instanceof TagGambit) {
                return;
            }
        }

        $query->whereNotExists(function ($query) {
            return $query->select(app('flarum.db')->raw(1))
                ->from('discussions_tags')
                ->whereIn('tag_id', Tag::where('is_hidden', 1)->lists('id'))
                ->where('discussions.id', new Expression('discussion_id'));
        });
    }
}
