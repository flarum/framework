<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Audit\Middleware;

use Flarum\Audit\AuditLogger;
use Flarum\Http\RequestUtil;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class SetLoggerActor implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        AuditLogger::$ipAddress = $request->getAttribute('ipAddress');
        AuditLogger::$actor = RequestUtil::getActor($request);
        AuditLogger::$path = $request->getUri()->getPath();

        if ($request->getAttribute('session')) {
            AuditLogger::$client = 'session';
        } elseif ($request->getAttribute('apiKey')) {
            AuditLogger::$client = 'api_key';
        } else {
            AuditLogger::$client = 'access_token';
        }

        return $handler->handle($request);
    }
}
