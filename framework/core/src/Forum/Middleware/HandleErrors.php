<?php
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Forum\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Zend\Diactoros\Response\HtmlResponse;
use Zend\Stratigility\ErrorMiddlewareInterface;

class HandleErrors implements ErrorMiddlewareInterface
{
    protected $templateDir;

    public function __construct($templateDir)
    {
        $this->templateDir = $templateDir;
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke($error, Request $request, Response $response, callable $out = null)
    {
        $status = 500;

        // If it seems to be a valid HTTP status code, we pass on the
        // exception's status.
        $errorCode = $error->getCode();
        if (is_int($errorCode) && $errorCode >= 400 && $errorCode < 600) {
            $status = $errorCode;
        }

        $errorPage = $this->getErrorPage($status);

        return new HtmlResponse($errorPage, $status);
    }

    protected function getErrorPage($status)
    {
        if (!file_exists($errorPage = $this->templateDir."/$status.html")) {
            $errorPage = $this->templateDir.'/500.html';
        }

        return file_get_contents($errorPage);
    }
}
