<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Forum\Controller;

use Flarum\Http\Controller\AbstractHtmlController;
use Flarum\Http\UrlGenerator;
use Flarum\Notification\UnsubscribeToken;
use Flarum\User\User;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Psr\Http\Message\ServerRequestInterface as Request;

class UnsubscribeController extends AbstractHtmlController
{
    public function __construct(
        protected UrlGenerator $url,
        protected Factory $view
    ) {
    }

    public function render(Request $request): View
    {
        $userId = Arr::get($request->getQueryParams(), 'userId');
        $token = Arr::get($request->getQueryParams(), 'token');

        // Fetch the unsubscribe token record
        $unsubscribeRecord = UnsubscribeToken::where('user_id', $userId)
            ->where('token', $token)
            ->first();

        // If record exists and has not been used before
        if ($unsubscribeRecord && ! $unsubscribeRecord->unsubscribed_at) {
            // Mark as unsubscribed
            $unsubscribeRecord->unsubscribed_at = Carbon::now();
            $unsubscribeRecord->save();

            // Update user preferences
            /** @var User $user */
            $user = User::find($userId);
            $user->setPreference('notify_'.$unsubscribeRecord->email_type.'_email', false);
            $user->save();

            $message = 'You have successfully unsubscribed from this type of email notification. If you wish to receive it again, please update your settings.';
        } else {
            // If the token doesn't exist or has already been used
            $message = 'This unsubscribe link is invalid or has already been used. Please check your settings if you wish to manage your email notifications.';
        }

        return $this->view->make('flarum.forum::unsubscribe-confirmation')->with('message', $message);
    }
}
