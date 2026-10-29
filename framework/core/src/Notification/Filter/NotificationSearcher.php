<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Notification\Filter;

use Flarum\Notification\NotificationRepository;
use Flarum\Search\Database\AbstractSearcher;
use Flarum\Search\Filter\FilterManager;
use Flarum\User\User;
use Illuminate\Database\Eloquent\Builder;

class NotificationSearcher extends AbstractSearcher
{
    public function __construct(
        FilterManager $filters,
        array $mutators,
        protected NotificationRepository $notifications
    ) {
        parent::__construct($filters, $mutators);
    }

    public function getQuery(User $actor): Builder
    {
        return $this->notifications->query($actor);
    }
}
