<?php namespace Flarum\Api;

use Flarum\Support\Actor;
use Illuminate\Http\Request as IlluminateRequest;

class Request
{
    public $input;

    public $actor;

    public $http;

    public function __construct(array $input, Actor $actor = null, IlluminateRequest $http = null)
    {
        $this->input = $input;
        $this->actor = $actor;
        $this->http = $http;
    }

    public function get($key, $default = null)
    {
        return array_get($this->input, $key, $default);
    }

    public function all()
    {
        return $this->input;
    }
}
