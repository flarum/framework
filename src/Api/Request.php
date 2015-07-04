<?php namespace Flarum\Api;

use Flarum\Core\Users\Guest;
use Flarum\Core\Users\User;
use Psr\Http\Message\ServerRequestInterface;

class Request
{
    /**
     * @var array
     */
    public $input;

    /**
     * @var Guest
     */
    public $actor;

    /**
     * @var ServerRequestInterface
     */
    public $http;

    /**
     * @param array $input
     * @param User $actor
     * @param ServerRequestInterface $http
     */
    public function __construct(array $input, User $actor = null, ServerRequestInterface $http = null)
    {
        $this->input = $input;
        $this->actor = $actor ?: new Guest;
        $this->http = $http;
    }

    /**
     * @param $key
     * @param null $default
     * @return mixed
     */
    public function get($key, $default = null)
    {
        return array_get($this->input, $key, $default);
    }

    /**
     * @return array
     */
    public function all()
    {
        return $this->input;
    }
}
