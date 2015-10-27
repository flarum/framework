<?php
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Api\Middleware;

use Flarum\Api\ErrorHandler;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Zend\Stratigility\ErrorMiddlewareInterface;

class HandleErrors implements ErrorMiddlewareInterface
{
    /**
     * @var ErrorHandler
     */
    protected $errorHandler;

    /**
     * @param ErrorHandler $errorHandler
     */
    public function __construct(ErrorHandler $errorHandler)
    {
        $this->errorHandler = $errorHandler;
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke($e, Request $request, Response $response, callable $out = null)
    {
        return $this->errorHandler->handle($e);
    }
}
