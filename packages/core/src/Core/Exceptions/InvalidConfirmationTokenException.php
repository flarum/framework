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

class InvalidConfirmationTokenException extends Exception implements JsonApiSerializable
{
    /**
     * {@inheritdoc}
     */
    public function getStatusCode()
    {
        return 403;
    }

    /**
     * {@inheritdoc}
     */
    public function getErrors()
    {
        return ['code' => 'invalid_confirmation_token'];
    }
}
