<?php namespace Flarum\Approval;

use Flarum\Support\Extension as BaseExtension;
use Illuminate\Events\Dispatcher;

class Extension extends BaseExtension
{
    public function listen(Dispatcher $events)
    {
        $events->subscribe('Flarum\Approval\Listeners\AddClientAssets');
        $events->subscribe('Flarum\Approval\Listeners\AddApiAttributes');
        $events->subscribe('Flarum\Approval\Listeners\HideUnapprovedContent');
        $events->subscribe('Flarum\Approval\Listeners\UnapproveNewContent');
        $events->subscribe('Flarum\Approval\Listeners\ApproveContent');
    }
}
