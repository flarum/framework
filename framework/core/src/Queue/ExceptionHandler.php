<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Queue;

use Exception;
use Illuminate\Contracts\Debug\ExceptionHandler as ExceptionHandling;
use Psr\Log\LoggerInterface;
use Throwable;

class ExceptionHandler implements ExceptionHandling
{
    public function __construct(
        private readonly LoggerInterface $logger
    ) {}

    /**
     * Report or log an exception.
     *
     * @param  Throwable $e
     * @return void
     */
    public function report(Throwable $e)
    {
        $this->logger->error((string) $e);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  Throwable               $e
     * @return void
     */
    public function render($request, Throwable $e) /** @phpstan-ignore-line */
    {
        // TODO: Implement render() method.
    }

    /**
     * Render an exception to the console.
     *
     * @param  \Symfony\Component\Console\Output\OutputInterface $output
     * @param  Throwable                                        $e
     * @return void
     */
    public function renderForConsole($output, Throwable $e)
    {
        // TODO: Implement renderForConsole() method.
    }

    /**
     * Determine if the exception should be reported.
     *
     * @param  Throwable $e
     * @return bool
     */
    public function shouldReport(Throwable $e)
    {
        return true;
    }
}
