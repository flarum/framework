<?php
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Api\Middleware;

use Flarum\Core\Exceptions\JsonApiSerializable;
use Illuminate\Contracts\Validation\ValidationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Zend\Diactoros\Response\JsonResponse;
use Zend\Stratigility\ErrorMiddlewareInterface;

class JsonApiErrors implements ErrorMiddlewareInterface
{
    /**
     * {@inheritdoc}
     */
    public function __invoke($error, Request $request, Response $response, callable $out = null)
    {
        if ($error instanceof JsonApiSerializable) {
            $status = $error->getStatusCode();

            $errors = $error->getErrors();
        } else if ($error instanceof ValidationException) {
            $status = 422;

            $errors = $error->errors()->toArray();
            $errors = array_map(function ($field, $messages) {
                return [
                    'detail' => implode("\n", $messages),
                    'path' => $field,
                ];
            }, array_keys($errors), $errors);
        } else if ($error instanceof ModelNotFoundException) {
            $status = 404;

            $errors = [];
        } else {
            $status = 500;

            // If it seems to be a valid HTTP status code, we pass on the
            // exception's status.
            $errorCode = $error->getCode();
            if (is_int($errorCode) && $errorCode >= 400 && $errorCode < 600) {
                $status = $errorCode;
            }

            $errors = [
                ['title' => $error->getMessage()]
            ];
        }

        // JSON API errors must be collected in an array under the
        // "errors" key in the top level of the document
        $data = [
            'errors' => $errors,
        ];

        return new JsonResponse($data, $status);
    }
}
