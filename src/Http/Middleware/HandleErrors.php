<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Http\Middleware;

use Flarum\Foundation\ErrorHandling\HttpFormatter;
use Flarum\Foundation\ErrorHandling\Registry;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface as Middleware;
use Psr\Http\Server\RequestHandlerInterface as Handler;
use Throwable;

/**
 * Catch exceptions thrown in a PSR-15 middleware stack and handle them safely.
 *
 * All errors will be rendered using the provided formatter. In addition,
 * unknown errors will be passed on to one or multiple
 * {@see \Flarum\Foundation\ErrorHandling\Reporter} instances.
 */
class HandleErrors implements Middleware
{
    /**
     * @var Registry
     */
    protected $registry;

    /**
     * @var HttpFormatter
     */
    protected $formatter;

    /**
     * @var \Flarum\Foundation\ErrorHandling\Reporter[]
     */
    protected $reporters;

    /**
     * @var array
     */
    protected $errorClasses;

    public function __construct(Registry $registry, HttpFormatter $formatter, iterable $reporters, $errorClasses = null)
    {
        $this->registry = $registry;
        $this->formatter = $formatter;
        $this->reporters = $reporters;
        $this->errorClasses = $errorClasses;
    }

    /**
     * Catch all errors that happen during further middleware execution.
     */
    public function process(Request $request, Handler $handler): Response
    {
        try {
            return $handler->handle($request);
        } catch (Throwable $e) {
            // If an array of allowlisted exception classes has been provided
            // (such as when we only want to handle frontend errors), we check
            // that the error inherits one of these classes. If not, we throw it
            // to let other handlers up in the middleware pipe handle it.
            if (is_array($this->errorClasses)) {
                $handled = false;

                foreach ($this->errorClasses as $errorClass) {
                    if ($e instanceof $errorClass) {
                        $handled = true;
                        break;
                    }
                }

                if (! $handled) {
                    throw $e;
                }
            }
            $error = $this->registry->handle($e);

            if ($error->shouldBeReported()) {
                foreach ($this->reporters as $reporter) {
                    $reporter->report($error->getException());
                }
            }

            return $this->formatter->format($error, $request);
        }
    }
}
