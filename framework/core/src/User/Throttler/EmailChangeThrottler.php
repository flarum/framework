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
use Illuminate\Support\Arr;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Users can request an email change,
 * this throttler applies a timeout of 5 minutes between requests.
 */
class EmailChangeThrottler
{
    public static int $timeout = 300;

    public function __invoke(ServerRequestInterface $request): ?bool
    {
        if ($request->getAttribute('routeName') !== 'users.update') {
            return null;
        }

        if (! Arr::has($request->getParsedBody(), 'data.attributes.email')) {
            return null;
        }

        $actor = RequestUtil::getActor($request);

        // Check that an email token was not already created recently (last 5 minutes).
        if (EmailToken::query()->where('user_id', $actor->id)->where('created_at', '>=', Carbon::now()->subSeconds(self::$timeout))->exists()) {
            return true;
        }

        return null;
    }
}
