<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Http;

use Flarum\Foundation\ErrorHandling\LogReporter;
use Flarum\Foundation\SiteInterface;
use Laminas\Diactoros\Response;
use Laminas\Diactoros\ServerRequest;
use Laminas\Diactoros\ServerRequestFactory;
use Laminas\HttpHandlerRunner\Emitter\SapiEmitter;
use Laminas\HttpHandlerRunner\RequestHandlerRunner;
use Laminas\Stratigility\Middleware\ErrorResponseGenerator;
use Psr\Log\LoggerInterface;
use Throwable;

class Server
{
    private $site;

    public function __construct(SiteInterface $site)
    {
        $this->site = $site;
    }

    public function listen()
    {
        $runner = new RequestHandlerRunner(
            $this->safelyBootAndGetHandler(),
            new SapiEmitter,
            [ServerRequestFactory::class, 'fromGlobals'],
            function (Throwable $e) {
                $generator = new ErrorResponseGenerator;

                return $generator($e, new ServerRequest, new Response);
            }
        );
        $runner->run();
    }

    /**
     * Try to boot Flarum, and retrieve the app's HTTP request handler.
     *
     * We catch all exceptions happening during this process and format them to
     * prevent exposure of sensitive information.
     *
     * @return \Psr\Http\Server\RequestHandlerInterface
     */
    private function safelyBootAndGetHandler()
    {
        try {
            return $this->site->bootApp()->getRequestHandler();
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
     */
    private function cleanBootExceptionLog(Throwable $error)
    {
        if (app()->has('flarum.config') && app('flarum.config')->inDebugMode()) {
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
        } elseif (app()->has(LoggerInterface::class)) {
            // If the application booted far enough for the logger to be available, we will log the error there
            // Considering most boot errors are related to database or extensions, the logger should already be loaded
            // We check for LoggerInterface binding because it's a constructor dependency of LogReporter,
            // then instantiate LogReporter through the container for automatic dependency injection
            app(LogReporter::class)->report($error);

            echo 'Flarum encountered a boot error. Details have been logged to the Flarum log file.';
            exit(1);
        }
    }

    /**
     * If the clean logging doesn't work, then we have a last opportunity.
     * Here we need to be extra careful not to include anything that might be sensitive on the page.
     * @param Throwable $error
     * @throws Throwable
     */
    private function fallbackBootExceptionLog(Throwable $error)
    {
        echo 'Flarum encountered a boot error. Details have been logged to the system PHP log file.<br />';

        // Throwing the exception ensures it will be visible with PHP display_errors=On
        // but invisible if that feature is turned off
        // PHP will also automatically choose a valid place to log it based on the system settings
        throw $error;
    }
}
