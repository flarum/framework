<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Audit\Integration;

use Flarum\Audit\AuditLogger;
use Flarum\User\User;
use FoF\UserRequest\UsernameRequest;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Events\Dispatcher;

/**
 * fof/username-request integration.
 *
 * Stateful: captures the old username/nickname on user update and reacts to the request
 * lifecycle. Wired through the audit extender's `using()` escape hatch.
 *
 * Note: this lives in flarum/audit for now. Once fof/username-request adopts the public
 * Flarum\Audit\Extend\Audit API, this integration can move into that extension's extend.php.
 */
class FoFUsernameRequestIntegration
{
    /**
     * @var string[]
     */
    public static $actions = [
        'user.nickname_requested',
        'user.nickname_request_approved',
        'user.nickname_request_rejected',
        'user.username_requested',
        'user.username_request_approved',
        'user.username_request_rejected',
    ];

    protected ?string $oldNickname = null;
    protected ?string $oldUsername = null;

    public function __invoke(Container $container): void
    {
        if (! class_exists(UsernameRequest::class)) {
            return;
        }

        // Listen on the events dispatcher rather than the static Model::event(Closure) API, so the
        // listeners aren't bound to the model classes' static dispatcher.
        $events = $container->make(Dispatcher::class);

        $events->listen('eloquent.updated: '.User::class, [$this, 'userUpdated']);
        $events->listen('eloquent.saved: '.UsernameRequest::class, [$this, 'requestSaved']);
    }

    public function userUpdated(User $user): void
    {
        $this->oldNickname = $user->getOriginal('nickname');
        $this->oldUsername = $user->getOriginal('username');
    }

    public function requestSaved(UsernameRequest $request): void
    {
        switch ($request->status) {
            case 'Sent':
                if ($request->for_nickname) {
                    AuditLogger::log('user.nickname_requested', [
                        'user_id' => $request->user_id,
                        'new_nickname' => $request->requested_username ?: null,
                    ]);
                } else {
                    AuditLogger::log('user.username_requested', [
                        'user_id' => $request->user_id,
                        'new_username' => $request->requested_username,
                    ]);
                }
                break;
            case 'Approved':
                if ($request->for_nickname) {
                    AuditLogger::log('user.nickname_request_approved', [
                        'user_id' => $request->user_id,
                        'old_nickname' => $this->oldNickname ?: null,
                        'new_nickname' => $request->requested_username ?: null,
                    ]);
                } else {
                    AuditLogger::log('user.username_request_approved', [
                        'user_id' => $request->user_id,
                        'old_username' => $this->oldUsername,
                        'new_username' => $request->requested_username,
                    ]);
                }
                break;
            case 'Rejected':
                if ($request->for_nickname) {
                    AuditLogger::log('user.nickname_request_rejected', [
                        'user_id' => $request->user_id,
                        'new_nickname' => $request->requested_username ?: null,
                        'reason' => $request->reason,
                    ]);
                } else {
                    AuditLogger::log('user.username_request_rejected', [
                        'user_id' => $request->user_id,
                        'new_username' => $request->requested_username,
                        'reason' => $request->reason,
                    ]);
                }
                break;
        }
    }
}
