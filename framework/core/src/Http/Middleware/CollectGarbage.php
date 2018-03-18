<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Http\Middleware;

use Flarum\Http\AccessToken;
use Flarum\User\AuthToken;
use Flarum\User\EmailToken;
use Flarum\User\PasswordToken;
use Interop\Http\ServerMiddleware\DelegateInterface;
use Interop\Http\ServerMiddleware\MiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface as Request;

class CollectGarbage implements MiddlewareInterface
{
    public function process(Request $request, DelegateInterface $delegate)
    {
        $this->collectGarbageSometimes();

        return $delegate->process($request);
    }

    private function collectGarbageSometimes()
    {
        // In order to save performance, we only execute this query
        // from time to time (with 2% chance).
        if (! $this->hit()) {
            return;
        }

        AccessToken::whereRaw('last_activity <= ? - lifetime', [time()])->delete();

        $earliestToKeep = date('Y-m-d H:i:s', time() - 24 * 60 * 60);

        EmailToken::where('created_at', '<=', $earliestToKeep)->delete();
        PasswordToken::where('created_at', '<=', $earliestToKeep)->delete();
        AuthToken::where('created_at', '<=', $earliestToKeep)->delete();
    }

    private function hit()
    {
        return mt_rand(1, 100) <= 2;
    }
}
