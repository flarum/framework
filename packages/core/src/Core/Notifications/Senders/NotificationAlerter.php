<?php namespace Flarum\Core\Notifications\Senders;

use Flarum\Core\Notifications\Types\Notification;
use Flarum\Core\Models\Notification as NotificationModel;
use ReflectionClass;

class NotificationAlerter implements NotificationSender
{
    public function send(Notification $notification)
    {
        $model = NotificationModel::alert(
            $notification->getRecipient()->id,
            $notification::getType(),
            $notification->getSender()->id,
            $notification->getSubject()->id,
            $notification->getAlertData()
        );
        $model->save();
    }

    public function compatibleWith($className)
    {
        return (new ReflectionClass($className))->implementsInterface('Flarum\Core\Notifications\Types\AlertableNotification');
    }
}
