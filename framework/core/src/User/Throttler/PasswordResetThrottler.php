<?php

namespace Flarum\User\Throttler;

use Carbon\Carbon;
use Flarum\Http\RequestUtil;
use Flarum\User\PasswordToken;
use Illuminate\Support\Arr;
use Psr\Http\Message\ServerRequestInterface;

class PasswordResetThrottler
{
    public static $timeout = 300;

    /**
     * @return bool|void
     */
    public function __invoke(ServerRequestInterface $request)
    {
        if ($request->getAttribute('routeName') !== 'forgot') {
            return;
        }

        if (! Arr::has($request->getParsedBody(), 'email')) {
            return;
        }

        $actor = RequestUtil::getActor($request);

        if (PasswordToken::query()->where('user_id', $actor->id)->where('created_at', '>=', Carbon::now()->subSeconds(self::$timeout))->exists()) {
            return true;
        }
    }
}
