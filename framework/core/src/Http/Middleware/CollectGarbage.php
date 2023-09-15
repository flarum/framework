<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Http\Middleware;

use Carbon\Carbon;
use Flarum\Http\AccessToken;
use Flarum\User\EmailToken;
use Flarum\User\PasswordToken;
use Flarum\User\RegistrationToken;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface as Middleware;
use Psr\Http\Server\RequestHandlerInterface as Handler;
use SessionHandlerInterface;

class CollectGarbage implements Middleware
{
    protected array $sessionConfig;

    public function __construct(
        protected SessionHandlerInterface $sessionHandler,
        ConfigRepository $config
    ) {
        $this->sessionConfig = (array) $config->get('session');
    }

    public function process(Request $request, Handler $handler): Response
    {
        $this->collectGarbageSometimes();

        return $handler->handle($request);
    }

    private function collectGarbageSometimes(): void
    {
        // In order to save performance, we only execute this query
        // from time to time (with 2% chance).
        if (! $this->hit()) {
            return;
        }

        $time = Carbon::now()->timestamp;

        AccessToken::whereExpired()->delete();

        $earliestToKeep = date('Y-m-d H:i:s', $time - 24 * 60 * 60);

        EmailToken::where('created_at', '<=', $earliestToKeep)->delete();
        PasswordToken::where('created_at', '<=', $earliestToKeep)->delete();
        RegistrationToken::where('created_at', '<=', $earliestToKeep)->delete();

        $this->sessionHandler->gc($this->getSessionLifetimeInSeconds());
    }

    private function hit(): bool
    {
        return mt_rand(1, 100) <= 2;
    }

    private function getSessionLifetimeInSeconds(): float|int
    {
        return $this->sessionConfig['lifetime'] * 60;
    }
}
