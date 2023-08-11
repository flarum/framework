<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Http\Middleware;

use Closure;
use Flarum\Foundation\ErrorHandling\HttpFormatter;
use Flarum\Foundation\ErrorHandling\Registry;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Catch exceptions thrown in a PSR-15 middleware stack and handle them safely.
 *
 * All errors will be rendered using the provided formatter. In addition,
 * unknown errors will be passed on to one or multiple
 * {@see \Flarum\Foundation\ErrorHandling\Reporter} instances.
 */
class HandleErrors implements IlluminateMiddlewareInterface
{
    public function __construct(
        protected Registry $registry,
        protected HttpFormatter $formatter,
        /** @var \Flarum\Foundation\ErrorHandling\Reporter[] $reporters */
        protected iterable $reporters
    ) {
    }

    /**
     * Catch all errors that happen during further middleware execution.
     */
    public function handle(Request $request, Closure $next): Response
    {
        try {
            return $next($request);
        } catch (Throwable $e) {
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
