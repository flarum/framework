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

        /** @var UnsubscribeToken|null $unsubscribeRecord */
        $unsubscribeRecord = UnsubscribeToken::where('user_id', $userId)
            ->where('token', $token)
            ->first();

        if ($unsubscribeRecord && empty($unsubscribeRecord->unsubscribed_at)) {
            $unsubscribeRecord->unsubscribed_at = Carbon::now();
            $unsubscribeRecord->save();

            /** @var User $user */
            $user = User::find($userId);
            $user->setNotificationPreference($unsubscribeRecord->email_type, 'email', false);
            $user->save();
        }

        return new RedirectResponse($this->url->to('forum')->base());
    }
}
