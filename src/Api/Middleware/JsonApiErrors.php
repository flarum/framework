<?php namespace Flarum\Api\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Zend\Diactoros\Response\StringResponse;
use Zend\Stratigility\ErrorMiddlewareInterface;

class JsonApiErrors implements ErrorMiddlewareInterface
{
    /**
     * {@inheritdoc}
     */
    public function __invoke($error, Request $request, Response $response, callable $out = null)
    {
        $errorObject = [
            'title' => $error->getMessage(),
        ];

        $status = 500;

        // If it seems to be a valid HTTP status code, we pass on the
        // exception's status.
        $errorCode = $error->getCode();
        if (is_int($errorCode) && $errorCode >= 400 && $errorCode < 600) {
            $status = $errorCode;
        }

        // JSON API errors must be collected in an array under the
        // "errors" key in the top level of the document
        $data = [
            'errors' => [$errorObject]
        ];

        return StringResponse::json($data, $status);
    }
}
