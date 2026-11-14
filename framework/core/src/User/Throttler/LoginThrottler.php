<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\User\Throttler;

use Illuminate\Cache\RateLimiter;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Limits failed logins, so passwords can't be guessed at full speed.
 *
 * Failures are counted twice: for one username or email from one IP address,
 * which stops guessing at one account, and for the IP address alone, which
 * stops trying a few passwords against many accounts. Neither is counted for
 * an account alone, so nobody can lock someone else out by failing to log in
 * as them.
 *
 * Failures are recorded by the RecordFailedLogins middleware, whichever
 * controller handles the route.
 */
class LoginThrottler
{
    public static int $maxAttempts = 5;

    public static int $decaySeconds = 60;

    public static int $maxAttemptsPerIp = 30;

    public static int $ipDecaySeconds = 600;

    public function __construct(
        protected RateLimiter $limiter
    ) {
    }

    public function __invoke(ServerRequestInterface $request): ?bool
    {
        if ($request->getAttribute('routeName') !== 'token') {
            return null;
        }

        [$accountKey, $ipKey] = $this->keys($request);

        if ($this->limiter->tooManyAttempts($accountKey, static::$maxAttempts)
            || $this->limiter->tooManyAttempts($ipKey, static::$maxAttemptsPerIp)) {
            return true;
        }

        return null;
    }

    public function recordFailure(ServerRequestInterface $request): void
    {
        [$accountKey, $ipKey] = $this->keys($request);

        $this->limiter->hit($accountKey, static::$decaySeconds);
        $this->limiter->hit($ipKey, static::$ipDecaySeconds);
    }

    /**
     * @return array{string, string}
     */
    protected function keys(ServerRequestInterface $request): array
    {
        $ip = (string) $request->getAttribute('ipAddress');

        $identification = Arr::get((array) $request->getParsedBody(), 'identification');
        $identification = is_string($identification) ? Str::lower(trim($identification)) : '';

        return [
            'login-attempts:'.sha1($ip.'|'.$identification),
            'login-attempts-ip:'.sha1($ip),
        ];
    }
}
