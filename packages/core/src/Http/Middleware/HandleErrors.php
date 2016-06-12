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

use Franzl\Middleware\Whoops\ErrorMiddleware as WhoopsMiddleware;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;
use Zend\Diactoros\Response\HtmlResponse;
use Zend\Stratigility\ErrorMiddlewareInterface;

class HandleErrors implements ErrorMiddlewareInterface
{
    /**
     * @var string
     */
    protected $templateDir;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var bool
     */
    protected $debug;

    /**
     * @param string $templateDir
     * @param LoggerInterface $logger
     * @param bool $debug
     */
    public function __construct($templateDir, LoggerInterface $logger, $debug = false)
    {
        $this->templateDir = $templateDir;
        $this->logger = $logger;
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

        if ($this->debug && ! in_array($errorCode, [403, 404])) {
            $whoops = new WhoopsMiddleware;

            return $whoops($error, $request, $response, $out);
        }

        // Log the exception (with trace)
        $this->logger->debug($error);

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
