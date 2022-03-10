<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Foundation;

use Flarum\Extension\Exception as ExtensionException;
use Flarum\Foundation\ErrorHandling as Handling;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException as IlluminateValidationException;
use Tobscure\JsonApi\Exception\InvalidParameterException;

class ErrorServiceProvider extends AbstractServiceProvider
{
    public function register()
    {
        $this->container->singleton('flarum.error.statuses', function () {
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

        $this->container->singleton('flarum.error.classes', function () {
            return [
                InvalidParameterException::class => 'invalid_parameter',
                ModelNotFoundException::class => 'not_found',
            ];
        });

        $this->container->singleton('flarum.error.handlers', function () {
            return [
                IlluminateValidationException::class => Handling\ExceptionHandler\IlluminateValidationExceptionHandler::class,
                ValidationException::class => Handling\ExceptionHandler\ValidationExceptionHandler::class,
                ExtensionException\DependentExtensionsException::class => ExtensionException\DependentExtensionsExceptionHandler::class,
                ExtensionException\MissingDependenciesException::class => ExtensionException\MissingDependenciesExceptionHandler::class,
            ];
        });

        $this->container->singleton(Handling\Registry::class, function () {
            return new Handling\Registry(
                $this->container->make('flarum.error.statuses'),
                $this->container->make('flarum.error.classes'),
                $this->container->make('flarum.error.handlers')
            );
        });

        $this->container->tag(Handling\LogReporter::class, Handling\Reporter::class);
    }
}
