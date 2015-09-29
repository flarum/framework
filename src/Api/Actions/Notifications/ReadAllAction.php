<?php
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Api\Actions\Notifications;

use Flarum\Core\Notifications\Commands\ReadAllNotifications;
use Illuminate\Contracts\Bus\Dispatcher;
use Flarum\Api\Request;
use Flarum\Api\Actions\DeleteAction;

class ReadAllAction extends DeleteAction
{
    /**
     * @var Dispatcher
     */
    protected $bus;

    /**
     * @param Dispatcher $bus
     */
    public function __construct(Dispatcher $bus)
    {
        $this->bus = $bus;
    }

    /**
     * Mark all notifications as read.
     *
     * @param Request $request
     * @return void
     */
    protected function delete(Request $request)
    {
        $this->bus->dispatch(
            new ReadAllNotifications($request->actor)
        );
    }
}
