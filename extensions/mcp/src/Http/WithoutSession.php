<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Mcp\Http;

use Dflydev\FigCookies\FigResponseCookies;
use Flarum\Http\CookieFactory;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Keeps /mcp and its public metadata out of the forum's browser session.
 *
 * Core has no per-route way to skip StartSession: it starts a session on every
 * forum request, saves it afterwards and always sends its cookie and
 * X-CSRF-Token. Replacing StartSession would be more invasive, so this sits
 * just outside it instead. For these routes it drops the request's cookies, so no
 * browser session or remember cookie is read or refreshed, and strips the
 * session cookie and CSRF header from the response; their controllers give the
 * throwaway session a null handler, so StartSession's save writes nothing.
 */
class WithoutSession implements MiddlewareInterface
{
    public const string PATH = '/mcp';

    public const array METADATA_PATHS = ['/.well-known/oauth-protected-resource/mcp', '/.well-known/oauth-protected-resource'];

    public function __construct(
        protected CookieFactory $cookies,
        protected ConfigRepository $config,
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (! in_array(rtrim($request->getUri()->getPath(), '/'), [static::PATH, ...static::METADATA_PATHS], true)) {
            return $handler->handle($request);
        }

        $response = $handler->handle($request->withCookieParams([]));

        return FigResponseCookies::remove($response, $this->cookies->getName((string) $this->config->get('session.cookie')))
            ->withoutHeader('X-CSRF-Token');
    }
}
