<?php namespace Flarum\Api;

use Tobscure\JsonApi\Document;
use Zend\Diactoros\Response;

class JsonApiResponse extends Response
{
    public function __construct(Document $document)
    {
        parent::__construct('php://memory', 200, ['content-type' => 'application/vnd.api+json']);

        $this->getBody()->write($document);
    }
}
