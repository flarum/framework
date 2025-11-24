<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Gdpr\Http\Controller;

use Carbon\Carbon;
use Flarum\Foundation\ValidationException;
use Flarum\Gdpr\Models\ErasureRequest;
use Flarum\Http\RequestUtil;
use Flarum\Http\SessionAuthenticator;
use Flarum\Http\UrlGenerator;
use Illuminate\Support\Arr;
use Laminas\Diactoros\Response\RedirectResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface;

class ConfirmErasureController implements RequestHandlerInterface
{
    public function __construct(protected UrlGenerator $url, protected SessionAuthenticator $authenticator)
    {
    }

    public function handle(Request $request): ResponseInterface
    {
        $actor = RequestUtil::getActor($request);
        $token = Arr::get($request->getQueryParams(), 'token');

        /** @var ErasureRequest $erasureRequest */
        $erasureRequest = ErasureRequest::query()
            ->with('user')
            ->where('verification_token', $token)
            ->firstOrFail();

        /**
         * @TODO: the token is enough to confirm the erasure request. We should not require the user to be logged in.
         */
        if ($erasureRequest->user->isNot($actor) && !$actor->isGuest()) {
            throw new ValidationException(['user' => 'Erase requests cannot be confirmed by different users.']);
        }

        $erasureRequest->user_confirmed_at = Carbon::now();
        $erasureRequest->status = ErasureRequest::STATUS_USER_CONFIRMED;
        $erasureRequest->cancelled_at = null;
        $erasureRequest->save();

        return new RedirectResponse($this->url->to('forum')->base().'?erasureRequestConfirmed=1');
    }
}
