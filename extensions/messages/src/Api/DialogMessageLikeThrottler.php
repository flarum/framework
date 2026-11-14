<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Messages\Api;

use Flarum\Http\RequestUtil;
use Illuminate\Cache\RateLimiter;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Limits how often one member may like or unlike messages. Each like goes
 * out live to every member of the conversation who is online, so flicking
 * one on and off would otherwise cost the forum a broadcast per request:
 * in all, and more tightly on any one message, which nobody changes their
 * mind about often. Those allowed to send messages without throttling
 * aren't limited here either.
 */
class DialogMessageLikeThrottler
{
    /** Likes and unlikes one member may make in a window. */
    public static int $maxAttempts = 30;

    /** Likes and unlikes one member may make of any one message in a window. */
    public static int $maxPerMessage = 4;

    public static int $decaySeconds = 60;

    public function __construct(
        protected RateLimiter $limiter
    ) {
    }

    public function __invoke(ServerRequestInterface $request): ?bool
    {
        if (! in_array($request->getAttribute('routeName'), ['dialog-messages.like', 'dialog-messages.unlike'], true)) {
            return null;
        }

        $actor = RequestUtil::getActor($request);

        if ($actor->isGuest() || $actor->can('dialog.sendMessageWithoutThrottle')) {
            return null;
        }

        $key = 'flarum-messages.likes:'.$actor->id;
        $messageKey = $key.':'.(int) ($request->getAttribute('routeParameters')['id'] ?? 0);

        if ($this->limiter->tooManyAttempts($key, static::$maxAttempts)
            || $this->limiter->tooManyAttempts($messageKey, static::$maxPerMessage)) {
            return true;
        }

        $this->limiter->hit($key, static::$decaySeconds);
        $this->limiter->hit($messageKey, static::$decaySeconds);

        return null;
    }
}
