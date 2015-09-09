<?php
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Core\Exceptions;

use Exception;

class ValidationException extends Exception implements JsonApiSerializable
{
    protected $messages;

    public function __construct(array $messages)
    {
        $this->messages = $messages;
    }

    public function getMessages()
    {
        return $this->messages;
    }

    /**
     * Return the HTTP status code to be used for this exception.
     *
     * @return int
     */
    public function getStatusCode()
    {
        return 422;
    }

    /**
     * Return an array of errors, formatted as JSON-API error objects.
     *
     * @see http://jsonapi.org/format/#error-objects
     * @return array
     */
    public function getErrors()
    {
        return array_map(function ($path, $detail) {
            return compact('path', 'detail');
        }, array_keys($this->messages), $this->messages);
    }
}
