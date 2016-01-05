<?php
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Api\Handler;

use Exception;
use Flarum\Core\Exception\ValidationException;
use Tobscure\JsonApi\Exception\Handler\ExceptionHandlerInterface;
use Tobscure\JsonApi\Exception\Handler\ResponseBag;

class ValidationExceptionHandler implements ExceptionHandlerInterface
{
    /**
     * {@inheritdoc}
     */
    public function manages(Exception $e)
    {
        return $e instanceof ValidationException;
    }

    /**
     * {@inheritdoc}
     */
    public function handle(Exception $e)
    {
        $status = 422;

        $messages = $e->getMessages();
        $errors = array_map(function ($path, $detail) use ($status) {
            return [
                'status' => (string) $status,
                'code' => 'validation_error',
                'detail' => $detail,
                'source' => ['pointer' => "/data/attributes/$path"]
            ];
        }, array_keys($messages), $messages);

        return new ResponseBag($status, $errors);
    }
}
