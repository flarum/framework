<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Messages;

use Flarum\Audit\AuditLogger;
use Flarum\Messages\DialogMessage\Event\Created;
use Flarum\User\User;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Events\Dispatcher;

/**
 * Who wrote to whom in private conversations, for flarum/audit. Never what was
 * written.
 */
class AuditIntegration
{
    public static array $actions = [
        'dialog.started',
        'dialog.posted',
    ];

    public function __invoke(Container $container): void
    {
        $container->make(Dispatcher::class)->listen(Created::class, [$this, 'messageCreated']);
    }

    public function messageCreated(Created $event): void
    {
        $message = $event->message;
        $dialog = $message->dialog;

        if (! $dialog) {
            return;
        }

        // Numbers are refreshed from the database before the event, and a
        // conversation's first message is always number 1.
        $action = (int) $message->number === 1 ? 'dialog.started' : 'dialog.posted';

        // One entry per recipient: audit shows and searches a single user per
        // entry, and conversations will not always be between two people.
        $dialog->users
            ->reject(fn (User $user) => $user->id === $message->user_id)
            ->each(fn (User $recipient) => AuditLogger::log($action, [
                'dialog_id' => $dialog->id,
                'user_id' => $recipient->id,
            ]));
    }
}
