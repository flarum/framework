<?php namespace Flarum\Api;

use Illuminate\Http\Response;
use Tobscure\JsonApi\Document;

class JsonApiResponse extends Response
{
    public $content;

    public function __construct($data = null, $status = 200, array $headers = [])
    {
        parent::__construct('', $status, $headers);

        $this->headers->set('Content-Type', 'application/vnd.api+json');

        $this->content = new Document;
        $this->content->setData($data);
    }
}
