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
use Illuminate\Support\Arr;
use Psr\Http\Message\ServerRequestInterface;
use Tobscure\JsonApi\Document;

class UpdateNotificationController extends AbstractShowController
{
    public ?string $serializer = NotificationSerializer::class;

    public function __construct(
        protected Dispatcher $bus
    ) {
    }

    protected function data(ServerRequestInterface $request, Document $document): Notification
    {
        $id = Arr::get($request->getQueryParams(), 'id');
        $actor = RequestUtil::getActor($request);

        return $this->bus->dispatch(
            new ReadNotification($id, $actor)
        );
    }
}
