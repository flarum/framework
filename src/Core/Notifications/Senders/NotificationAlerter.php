<?php namespace Flarum\Core\Notifications\Senders;

use Flarum\Core\Notifications\Types\Notification;
use Flarum\Core\Models\Notification as NotificationModel;
use Flarum\Core\Models\User;
use ReflectionClass;

class NotificationAlerter implements NotificationSender, RetractableSender
{
    public function send(Notification $notification, User $user)
    {
        $model = NotificationModel::alert(
            $user->id,
            $notification::getType(),
            $notification->getSender()->id,
            $notification->getSubject()->id,
            $notification->getAlertData()
        );

        $model->save();
    }

    public function retract(Notification $notification)
    {
        $models = NotificationModel::where('type', $notification::getType())
            ->where('subject_id', $notification->getSubject()->id)
            ->delete();
    }

    public static function compatibleWith($className)
    {
        return (new ReflectionClass($className))->implementsInterface('Flarum\Core\Notifications\Types\AlertableNotification');
    }
}
