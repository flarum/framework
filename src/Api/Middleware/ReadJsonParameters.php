<?php namespace Flarum\Api\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Zend\Stratigility\MiddlewareInterface;

class ReadJsonParameters implements MiddlewareInterface
{
    /**
     * {@inheritdoc}
     */
    public function __invoke(Request $request, Response $response, callable $out = null)
    {
        if (str_contains($request->getHeaderLine('content-type'), 'application/vnd.api+json')) {
            $input = json_decode($request->getBody(), true);

            foreach ($input as $name => $value) {
                $request = $request->withAttribute($name, $value);
            }
        }

        return $out ? $out($request, $response) : $response;
    }
}
