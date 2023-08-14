<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Foundation\ErrorHandling;

use Flarum\Foundation\Config;
use Illuminate\Contracts\Debug\ExceptionHandler as ExceptionHandling;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Symfony\Component\Console\Application as ConsoleApplication;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Catch exceptions thrown in the routing stack and console and handle them safely.
 *
 * All errors will be rendered using the provided formatter. In addition,
 * unknown errors will be passed on to one or multiple
 * {@see \Flarum\Foundation\ErrorHandling\Reporter} instances.
 */
class ExceptionHandler implements ExceptionHandling
{
    protected array $handledErrors = [];

    public function __construct(
        protected readonly Registry $registry,
        protected readonly iterable $formatters,
        /** @var \Flarum\Foundation\ErrorHandling\Reporter[] $reporters */
        protected readonly iterable $reporters,
        protected readonly Config $config
    ) {
    }

    public function report(Throwable $e): void
    {
        $error = $this->getHandledError($e);

        if ($error->shouldBeReported()) {
            foreach ($this->reporters as $reporter) {
                $reporter->report($e);
            }
        }
    }

    public function render($request, Throwable $e): Response /** @phpstan-ignore-line */
    {
        return $this->resolveFormatter($request)->format(
            $this->getHandledError($e), $request
        );
    }

    public function renderForConsole($output, Throwable $e): void
    {
        (new ConsoleApplication())->renderThrowable($e, $output);
    }

    public function shouldReport(Throwable $e): bool
    {
        return $this->getHandledError($e)->shouldBeReported();
    }

    /**
     * Get and cache the handled error for the given exception.
     */
    protected function getHandledError(Throwable $e): HandledError
    {
        return $this->handledErrors[$this->exceptionKey($e)] ??= $this->registry->handle($e);
    }

    /**
     * Get a unique key for the given exception.
     */
    protected function exceptionKey(Throwable $e): string
    {
        return get_class($e).':'.$e->getMessage().$e->getLine();
    }

    protected function resolveFormatter(Request $request): HttpFormatter
    {
        $isApiFrontend = explode('/', trim($request->path(), '/'))[0] === $this->config->path('api');

        return match (true) {
            $request->expectsJson(),
            $isApiFrontend                      => Arr::first($this->formatters, fn (HttpFormatter $formatter) => $formatter instanceof JsonApiFormatter),
            $this->config->inDebugMode()        => Arr::first($this->formatters, fn (HttpFormatter $formatter) => $formatter instanceof WhoopsFormatter),
            default                             => Arr::first($this->formatters, fn (HttpFormatter $formatter) => $formatter instanceof ViewFormatter),
        };
    }
}
