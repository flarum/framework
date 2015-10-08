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

use Flarum\Api\JsonApiResponse;
use Flarum\Foundation\Application;
use Illuminate\Contracts\Validation\ValidationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Tobscure\JsonApi\Document;
use Tobscure\JsonApi\Exception\JsonApiSerializableInterface;
use Zend\Diactoros\Response\JsonResponse;
use Zend\Stratigility\ErrorMiddlewareInterface;
use Flarum\Core;
use Exception;

class HandleErrors implements ErrorMiddlewareInterface
{
    /**
     * @var Application
     */
    private $app;

    /**
     * @param Application $app
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke($e, Request $request, Response $response, callable $out = null)
    {
        return $this->handle($e);
    }

    public function handle(Exception $e)
    {
        if ($e instanceof JsonApiSerializableInterface) {
            $status = $e->getStatusCode();

            $errors = $e->getErrors();
        } elseif ($e instanceof ValidationException) {
            $status = 422;

            $errors = $e->errors()->toArray();
            $errors = array_map(function ($field, $messages) {
                return [
                    'detail' => implode("\n", $messages),
                    'source' => ['pointer' => '/data/attributes/' . $field],
                ];
            }, array_keys($errors), $errors);
        } elseif ($e instanceof ModelNotFoundException) {
            $status = 404;

            $errors = [];
        } else {
            $status = 500;

            $error = [
                'code' => $status,
                'title' => 'Internal Server Error'
            ];

            if ($this->app->inDebugMode()) {
                $error['detail'] = (string) $e;
            }

            $errors = [$error];
        }

        $document = new Document;
        $document->setErrors($errors);

        return new JsonApiResponse($document, $status);
    }
}
