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

use Flarum\Foundation\ErrorHandling\Formatter;
use Flarum\Foundation\ErrorHandling\Registry;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface as Middleware;
use Psr\Http\Server\RequestHandlerInterface as Handler;
use Throwable;

class HandleErrors implements Middleware
{
    /**
     * @var Registry
     */
    protected $registry;

    /**
     * @var Formatter
     */
    protected $formatter;

    /**
     * @var \Flarum\Foundation\ErrorHandling\Reporter[]
     */
    protected $reporters;

    public function __construct(Registry $registry, Formatter $formatter, array $reporters)
    {
        $this->registry = $registry;
        $this->formatter = $formatter;
        $this->reporters = $reporters;
    }

    /**
     * Catch all errors that happen during further middleware execution.
     */
    public function process(Request $request, Handler $handler): Response
    {
        try {
            return $handler->handle($request);
        } catch (Throwable $e) {
            $error = $this->registry->handle($e);

            if ($error->shouldBeReported()) {
                foreach ($this->reporters as $reporter) {
                    $reporter->report($error);
                }
            }

            return $this->formatter->format($error, $request);
        }
    }
}
