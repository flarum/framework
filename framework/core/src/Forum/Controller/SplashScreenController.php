<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Forum\Controller;

use Flarum\Forum\Branding\SplashScreen;
use Illuminate\Support\Arr;
use Laminas\Diactoros\Response;
use Laminas\Diactoros\Response\EmptyResponse;
use Laminas\Diactoros\Stream;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class SplashScreenController implements RequestHandlerInterface
{
    public function __construct(
        protected SplashScreen $splashScreen
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $query = $request->getQueryParams();

        if (! preg_match('/^(\d{1,5})x(\d{1,5})$/', (string) Arr::get($query, 'size'), $size)
            || ($image = $this->splashScreen->image((int) $size[1], (int) $size[2])) === null) {
            return new EmptyResponse(404);
        }

        $body = new Stream('php://temp', 'wb+');
        $body->write($image);
        $body->rewind();

        return new Response($body, 200, [
            'Content-Type' => $this->splashScreen->type(),
            'Cache-Control' => Arr::get($query, 'v') === $this->splashScreen->version()
                ? 'public, max-age=31536000, immutable'
                : 'public, max-age=3600',
        ]);
    }
}
