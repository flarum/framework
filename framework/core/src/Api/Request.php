<?php
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Api;

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
     * @var User
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
