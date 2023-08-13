<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Api\Controller;

use Flarum\Api\Serializer\NotificationSerializer;
use Flarum\Http\RequestUtil;
use Flarum\Notification\Command\ReadNotification;
use Flarum\Notification\Notification;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Tobscure\JsonApi\Document;

class UpdateNotificationController extends AbstractShowController
{
    public ?string $serializer = NotificationSerializer::class;

    public function __construct(
        protected Dispatcher $bus
    ) {
    }

    protected function data(Request $request, Document $document): Notification
    {
        $id = $request->route('id');
        $actor = RequestUtil::getActor($request);

        return $this->bus->dispatch(
            new ReadNotification($id, $actor)
        );
    }
}
