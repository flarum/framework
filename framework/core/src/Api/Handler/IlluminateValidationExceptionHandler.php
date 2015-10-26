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
use Illuminate\Contracts\Validation\ValidationException;
use Tobscure\JsonApi\Exception\Handler\ExceptionHandlerInterface;
use Tobscure\JsonApi\Exception\Handler\ResponseBag;

class IlluminateValidationExceptionHandler implements ExceptionHandlerInterface
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

        $errors = $e->errors()->toArray();
        $errors = array_map(function ($field, $messages) {
            return [
                'detail' => implode("\n", $messages),
                'source' => ['pointer' => '/data/attributes/' . $field],
            ];
        }, array_keys($errors), $errors);

        return new ResponseBag($status, $errors);
    }
}
