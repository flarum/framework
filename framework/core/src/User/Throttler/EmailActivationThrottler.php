<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\User\Throttler;

use Carbon\Carbon;
use Flarum\Http\RequestUtil;
use Flarum\User\EmailToken;
use Illuminate\Http\Request;

/**
 * Unactivated users can request a confirmation email,
 * this throttler applies a timeout of 5 minutes between confirmation requests.
 */
class EmailActivationThrottler
{
    public static int $timeout = 300;

    public function __invoke(Request $request): ?bool
    {
        if ($request->routeIs('api.users.confirmation.send')) {
            return null;
        }

        $actor = RequestUtil::getActor($request);

        if (EmailToken::query()
            ->where('user_id', $actor->id)
            ->where('email', $actor->email)
            ->where('created_at', '>=', Carbon::now()->subSeconds(self::$timeout))
            ->exists()) {
            return true;
        }

        return null;
    }
}
