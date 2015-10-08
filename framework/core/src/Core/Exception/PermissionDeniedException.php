<?php
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Core\Exception;

use Exception;
use Tobscure\JsonApi\Exception\JsonApiSerializableInterface;

class PermissionDeniedException extends Exception implements JsonApiSerializableInterface
{
    /**
     * Return the HTTP status code to be used for this exception.
     *
     * @return int
     */
    public function getStatusCode()
    {
        return 401;
    }

    /**
     * Return an array of errors, formatted as JSON-API error objects.
     *
     * @see http://jsonapi.org/format/#error-objects
     * @return array
     */
    public function getErrors()
    {
        return [];
    }
}
