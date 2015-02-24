<?php namespace Flarum\Api\Events;

class WillRespondWithDocument
{
    public $document;

    public $statusCode;

    public $headers;

    public function __construct($document, &$statusCode, &$headers)
    {
        $this->document = $document;
        $this->statusCode = $statusCode;
        $this->headers = $headers;
    }
}
