<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Forum\Controller;

use Carbon\Carbon;
use Flarum\Http\UrlGenerator;
use Flarum\Notification\UnsubscribeToken;
use Flarum\User\User;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Support\Arr;
use Laminas\Diactoros\Response\RedirectResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface;

class UnsubscribeActionController implements RequestHandlerInterface
{
    public function __construct(
        protected Dispatcher $bus,
        protected UrlGenerator $url
    ) {
    }

    public function handle(Request $request): ResponseInterface
    {
        $parsedBody = $request->getParsedBody();
        $token = Arr::get($parsedBody, 'token');
        $userId = Arr::get($parsedBody, 'userId');

        // Fetch the unsubscribe token record
        /** @var UnsubscribeToken|null $unsubscribeRecord */
        $unsubscribeRecord = UnsubscribeToken::where('user_id', $userId)
            ->where('token', $token)
            ->first();

        // If record exists and has not been used before
        if ($unsubscribeRecord && empty($unsubscribeRecord->unsubscribed_at)) {
            // Mark as unsubscribed
            $unsubscribeRecord->unsubscribed_at = Carbon::now();
            $unsubscribeRecord->save();

            // Update user preferences
            /** @var User $user */
            $user = User::find($userId);
            $user->setNotificationPreference($unsubscribeRecord->email_type, 'email', false);
            $user->save();
        }

        // Redirect back to the forum's home page after unsubscribing
        return new RedirectResponse($this->url->to('forum')->base());
    }
}
