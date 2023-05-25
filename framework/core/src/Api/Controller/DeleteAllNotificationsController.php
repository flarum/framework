<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Api\Controller;

use Flarum\Http\RequestUtil;
use Flarum\Notification\Command\DeleteAllNotifications;
use Illuminate\Contracts\Bus\Dispatcher;
use Psr\Http\Message\ServerRequestInterface;

class DeleteAllNotificationsController extends AbstractDeleteController
{
    public function __construct(
        protected Dispatcher $bus
    ) {
    }

    protected function delete(ServerRequestInterface $request): void
    {
        $this->bus->dispatch(
            new DeleteAllNotifications(RequestUtil::getActor($request))
        );
    }
}
