<?php
namespace tests\Test\Stubs;

use Exception;
use Flarum\Core\Exceptions\JsonApiSerializable;

class ApiException extends Exception implements JsonApiSerializable
{
    public function getStatusCode()
    {
        return 599;
    }

    public function getErrors()
    {
        return ['error1', 'error2'];
    }
}
