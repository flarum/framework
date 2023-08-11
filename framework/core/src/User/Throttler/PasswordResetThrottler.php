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
use Flarum\User\PasswordToken;
use Illuminate\Http\Request;

/**
 * Logged-in users can request password reset email,
 * this throttler applies a timeout of 5 minutes between password resets.
 * This does not apply to guests requesting password resets.
 */
class PasswordResetThrottler
{
    public static int $timeout = 300;

    public function __invoke(Request $request): ?bool
    {
        if ($request->routeIs('forgot')) {
            return null;
        }

        if (! $request->has('email')) {
            return null;
        }

        $actor = RequestUtil::getActor($request);

        if (PasswordToken::query()->where('user_id', $actor->id)->where('created_at', '>=', Carbon::now()->subSeconds(self::$timeout))->exists()) {
            return true;
        }

        return null;
    }
}
