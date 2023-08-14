<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Foundation;

use Flarum\Foundation\ErrorHandling\LogReporter;
use Illuminate\Contracts\Foundation\Application as ApplicationContract;
use Psr\Log\LoggerInterface;
use Throwable;

class SafeBooter
{
    public function __construct(
        protected ApplicationContract $app
    ) {
    }

    /**
     * Try to boot Flarum, and retrieve the app's HTTP request handler.
     *
     * We catch all exceptions happening during this process and format them to
     * prevent exposure of sensitive information.
     *
     * @throws Throwable
     */
    public function boot(): void
    {
        try {
            $this->app->boot();
        } catch (Throwable $e) {
            // Apply response code first so whatever happens, it's set before anything is printed
            http_response_code(500);

            try {
                $this->cleanBootExceptionLog($e);
            } catch (Throwable $e) {
                // Ignore errors in logger. The important goal is to log the original error
            }

            $this->fallbackBootExceptionLog($e);
        }
    }

    /**
     * Attempt to log the boot exception in a clean way and stop the script execution.
     * This means looking for debug mode and/or our normal error logger.
     * There is always a risk for this to fail,
     * for example if the container bindings aren't present
     * or if there is a filesystem error.
     * @param Throwable $error
     * @throws Throwable
     */
    private function cleanBootExceptionLog(Throwable $error): void
    {
        if ($this->app->has('flarum.config') && resolve('flarum.config')->inDebugMode()) {
            // If the application booted far enough for the config to be available, we will check for debug mode
            // Since the config is loaded very early, it is very likely to be available from the container
            $message = $error->getMessage();
            $file = $error->getFile();
            $line = $error->getLine();
            $type = get_class($error);

            echo <<<ERROR
            Flarum encountered a boot error ($type)<br />
            <b>$message</b><br />
            thrown in <b>$file</b> on line <b>$line</b>

<pre>$error</pre>
ERROR;
            exit(1);
        } elseif ($this->app->has(LoggerInterface::class)) {
            // If the application booted far enough for the logger to be available, we will log the error there
            // Considering most boot errors are related to database or extensions, the logger should already be loaded
            // We check for LoggerInterface binding because it's a constructor dependency of LogReporter,
            // then instantiate LogReporter through the container for automatic dependency injection
            resolve(LogReporter::class)->report($error);

            echo 'Flarum encountered a boot error. Details have been logged to the Flarum log file.';
            exit(1);
        }
    }

    /**
     * If the clean logging doesn't work, then we have a last opportunity.
     * Here we need to be extra careful not to include anything that might be sensitive on the page.
     *
     * @throws Throwable
     */
    private function fallbackBootExceptionLog(Throwable $error): void
    {
        echo 'Flarum encountered a boot error. Details have been logged to the system PHP log file.<br />';

        // Throwing the exception ensures it will be visible with PHP display_errors=On
        // but invisible if that feature is turned off
        // PHP will also automatically choose a valid place to log it based on the system settings
        throw $error;
    }
}
