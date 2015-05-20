<?php namespace Flarum\Core\Notifications;

use Flarum\Core\Repositories\NotificationRepositoryInterface;
use Flarum\Core\Models\Notification;
use Carbon\Carbon;
use Closure;

class NotificationSyncer
{
    protected $onePerUser = false;

    protected $sentTo = [];

    protected $notifications;

    protected $mailer;

    public function __construct(NotificationRepositoryInterface $notifications, NotificationMailer $mailer)
    {
        $this->notifications = $notifications;
        $this->mailer = $mailer;
    }

    /**
     * Sync a notification so that it is visible to the specified users, and not
     * visible to anyone else. If it is being made visible for the first time,
     * attempt to send the user an email.
     *
     * @param \Flarum\Core\Notifications\NotificationInterface $notification
     * @param \Flarum\Core\Models\User[] $users
     * @return void
     */
    public function sync(NotificationInterface $notification, array $users)
    {
        $attributes = [
            'type'       => $notification::getType(),
            'sender_id'  => $notification->getSender()->id,
            'subject_id' => $notification->getSubject()->id,
            'data'       => ($data = $notification->getData()) ? json_encode($data) : null
        ];

        $toDelete = Notification::where($attributes)->get();
        $toUndelete = [];
        $newRecipients = [];

        foreach ($users as $user) {
            $existing = $toDelete->where('user_id', $user->id)->first();

            if (($k = $toDelete->search($existing)) !== false) {
                $toUndelete[] = $existing->id;
                $toDelete->pull($k);
            } elseif (! $this->onePerUser || ! in_array($user->id, $this->sentTo)) {
                $newRecipients[] = $user;
                $this->sentTo[] = $user->id;
            }
        }

        if (count($toDelete)) {
            Notification::whereIn('id', $toDelete->lists('id'))->update(['is_deleted' => true]);
        }

        if (count($toUndelete)) {
            Notification::whereIn('id', $toUndelete)->update(['is_deleted' => false]);
        }

        if (count($newRecipients)) {
            $now = Carbon::now('utc')->toDateTimeString();

            Notification::insert(
                array_map(function ($user) use ($attributes, $notification, $now) {
                    return $attributes + ['user_id' => $user->id, 'time' => $now];
                }, $newRecipients)
            );

            foreach ($newRecipients as $user) {
                if ($user->shouldEmail($notification::getType())) {
                    $this->mailer->send($notification, $user);
                }
            }
        }
    }

    public function onePerUser(Closure $callback)
    {
        $this->sentTo = [];
        $this->onePerUser = true;

        $callback();

        $this->onePerUser = false;
    }
}
