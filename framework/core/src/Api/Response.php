<?php namespace Flarum\Api;

use Psr\Http\Message\ResponseInterface;

class Response
{
    public function __construct(ResponseInterface $response)
    {
        $this->response = $response;
    }

    public function getBody()
    {
        return json_decode($this->response->getBody());
    }

    public function getStatusCode()
    {
        return $this->response->getStatusCode();
    }
}
