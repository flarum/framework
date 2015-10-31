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

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Zend\Diactoros\Response\HtmlResponse;
use Zend\Stratigility\ErrorMiddlewareInterface;
use Franzl\Middleware\Whoops\ErrorMiddleware as WhoopsMiddleware;

class HandleErrors implements ErrorMiddlewareInterface
{
    /**
     * @var string
     */
    protected $templateDir;

    /**
     * @var bool
     */
    protected $debug;

    /**
     * @param string $templateDir
     * @param bool $debug
     */
    public function __construct($templateDir, $debug = false)
    {
        $this->templateDir = $templateDir;
        $this->debug = $debug;
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke($error, Request $request, Response $response, callable $out = null)
    {
        $status = 500;
        $errorCode = $error->getCode();

        // If it seems to be a valid HTTP status code, we pass on the
        // exception's status.
        if (is_int($errorCode) && $errorCode >= 400 && $errorCode < 600) {
            $status = $errorCode;
        }

        if ($this->debug && $errorCode !== 404) {
            $whoops = new WhoopsMiddleware;

            return $whoops($error, $request, $response, $out);
        }

        $errorPage = $this->getErrorPage($status);

        return new HtmlResponse($errorPage, $status);
    }

    /**
     * @param string $status
     * @return string
     */
    protected function getErrorPage($status)
    {
        if (! file_exists($errorPage = $this->templateDir."/$status.html")) {
            $errorPage = $this->templateDir.'/500.html';
        }

        return file_get_contents($errorPage);
    }
}
