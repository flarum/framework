<?php

namespace Flarum\Http;

use Exception;

class RouteNotFoundException extends Exception
{
    public function __construct($message = null, $code = 404, Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
