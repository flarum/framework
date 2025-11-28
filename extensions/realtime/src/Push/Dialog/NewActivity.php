<?php

declare(strict_types=1);

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Realtime\Push\Dialog;

use Flarum\Messages\DialogMessage\Event\Created;
use Flarum\Realtime\Push\Jobs\SendDialogMessageJob;
use Flarum\Realtime\Push\Subscriber;
use Illuminate\Contracts\Events\Dispatcher;

class NewActivity extends Subscriber
{
    public function subscribe(Dispatcher $events): void
    {
        $this->listen(Created::class, [$this, 'created']);
    }

    public function created(Created $event): void
    {
        $this->queue()->push(new SendDialogMessageJob(
            get_class($event),
            $event->message,
        ));
    }
}
