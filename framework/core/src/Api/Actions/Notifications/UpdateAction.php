<?php namespace Flarum\Api\Actions\Notifications;

use Flarum\Core\Commands\ReadNotificationCommand;
use Flarum\Api\Actions\BaseAction;
use Flarum\Api\Actions\ApiParams;
use Flarum\Api\Serializers\NotificationSerializer;

class UpdateAction extends BaseAction
{
    /**
     * Edit a discussion. Allows renaming the discussion, and updating its read
     * state with regards to the current user.
     *
     * @return Response
     */
    protected function run(ApiParams $params)
    {
        $notificationId = $params->get('id');
        $user = $this->actor->getUser();

        // if ($params->get('notifications.isRead')) {
            $command = new ReadNotificationCommand($notificationId, $user);
            $notification = $this->dispatch($command, $params);
        // }

        // Presumably, the discussion was updated successfully. (One of the command
        // handlers would have thrown an exception if not.) We set this
        // discussion as our document's primary element.
        $serializer = new NotificationSerializer;
        $document = $this->document()->setData($serializer->resource($notification));

        return $this->respondWithDocument($document);
    }
}
