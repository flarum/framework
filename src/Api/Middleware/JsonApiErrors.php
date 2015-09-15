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
use Flarum\Core;
use Exception;

class JsonApiErrors implements ErrorMiddlewareInterface
{
    /**
     * {@inheritdoc}
     */
    public function __invoke($e, Request $request, Response $response, callable $out = null)
    {
        return $this->handle($e);
    }

    public function handle(Exception $e)
    {
        if ($e instanceof JsonApiSerializable) {
            $status = $e->getStatusCode();

            $errors = $e->getErrors();
        } else if ($e instanceof ValidationException) {
            $status = 422;

            $errors = $e->errors()->toArray();
            $errors = array_map(function ($field, $messages) {
                return [
                    'detail' => implode("\n", $messages),
                    'source' => ['pointer' => '/data/attributes/' . $field],
                ];
            }, array_keys($errors), $errors);
        } else if ($e instanceof ModelNotFoundException) {
            $status = 404;

            $errors = [];
        } else {
            $status = 500;

            $error = [
                'code' => $status,
                'title' => 'Internal Server Error'
            ];

            if (Core::inDebugMode()) {
                $error['detail'] = (string) $e;
            }

            $errors = [$error];
        }

        // JSON API errors must be collected in an array under the
        // "errors" key in the top level of the document
        $data = [
            'errors' => $errors,
        ];

        return new JsonResponse($data, $status);
    }
}
