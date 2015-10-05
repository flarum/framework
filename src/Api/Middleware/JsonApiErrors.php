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

use Flarum\Support\Json\ErrorHandler;
use Flarum\Support\Json\ResponseBag;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Zend\Diactoros\Response\JsonResponse;
use Zend\Stratigility\ErrorMiddlewareInterface;
use Flarum\Core;
use Exception;

/**
 * Manages any exceptions thrown by the API, and formats the response accordingly.
 */
class JsonApiErrors implements ErrorMiddlewareInterface
{
    /**
     * @var ErrorHandler
     */
    private $errorHandler;

    /**
     * @param ErrorHandler $errorHandler
     */
    public function __construct(ErrorHandler $errorHandler)
    {
        $this->errorHandler = $errorHandler;
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke($e, Request $request, Response $response, callable $out = null)
    {
        return $this->handle($e);
    }

    /**
     * Handles the exception thrown via the error handler.
     *
     * @param Exception $e
     * @return JsonResponse
     */
    public function handle(Exception $e)
    {
        $response = $this->errorHandler->handle($e);

        return $this->formatResponse($response);
    }
    
    /**
     * Constructs the format necessary for the response back to the client.
     *
     * @param ResponseBag $response
     * @return JsonResponse
     */
    private function formatResponse(ResponseBag $response)
    {
        $data = [
            'errors' => $response->getErrors(),
        ];

        return new JsonResponse($data, $response->getStatus());
    }
}
