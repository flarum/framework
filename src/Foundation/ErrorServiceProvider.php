<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Foundation;

use Flarum\Foundation\ErrorHandling\ExceptionHandler;
use Flarum\Foundation\ErrorHandling\LogReporter;
use Flarum\Foundation\ErrorHandling\Registry;
use Flarum\Foundation\ErrorHandling\Reporter;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException as IlluminateValidationException;
use Tobscure\JsonApi\Exception\InvalidParameterException;

class ErrorServiceProvider extends AbstractServiceProvider
{
    public function register()
    {
        $this->app->singleton('flarum.error.statuses', function () {
            return [
                // 400 Bad Request
                'csrf_token_mismatch' => 400,
                'invalid_parameter' => 400,

                // 401 Unauthorized
                'invalid_access_token' => 401,
                'not_authenticated' => 401,

                // 403 Forbidden
                'invalid_confirmation_token' => 403,
                'permission_denied' => 403,

                // 404 Not Found
                'not_found' => 404,

                // 405 Method Not Allowed
                'method_not_allowed' => 405,

                // 429 Too Many Requests
                'too_many_requests' => 429,
            ];
        });

        $this->app->singleton('flarum.error.classes', function () {
            return [
                InvalidParameterException::class => 'invalid_parameter',
                ModelNotFoundException::class => 'not_found',
            ];
        });

        $this->app->singleton('flarum.error.handlers', function () {
            return [
                IlluminateValidationException::class => ExceptionHandler\IlluminateValidationExceptionHandler::class,
                ValidationException::class => ExceptionHandler\ValidationExceptionHandler::class,
            ];
        });

        $this->app->singleton(Registry::class, function () {
            return new Registry(
                $this->app->make('flarum.error.statuses'),
                $this->app->make('flarum.error.classes'),
                $this->app->make('flarum.error.handlers')
            );
        });

        $this->app->tag(LogReporter::class, Reporter::class);
    }
}
