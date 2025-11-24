<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Gdpr\Jobs;

use Flarum\Gdpr\Events\Exported;
use Flarum\Gdpr\Events\Exporting;
use Flarum\Gdpr\Exporter;
use Flarum\Gdpr\Models\Export;
use Flarum\Gdpr\Notifications\ExportAvailableBlueprint;
use Flarum\Notification\NotificationSyncer;
use Flarum\User\User;
use Illuminate\Contracts\Events\Dispatcher;

class ExportJob extends GdprJob
{
    public function __construct(private User $user, private User $actor)
    {
    }

    public function handle(Exporter $exporter, NotificationSyncer $notifications, Dispatcher $events)
    {
        $events->dispatch(new Exporting($this->user, $this->actor));

        $export = $exporter->export($this->user, $this->actor);

        $this->notify($export, $notifications);

        $events->dispatch(new Exported($this->user, $this->actor));
    }

    public function notify(Export $export, NotificationSyncer $notifications)
    {
        $notifications->sync(
            new ExportAvailableBlueprint($export),
            [$this->actor]
        );
    }
}
