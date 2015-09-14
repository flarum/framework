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

        parent::__construct(implode("\n", $messages));
    }

    public function getMessages()
    {
        return $this->messages;
    }

    /**
     * {@inheritdoc}
     */
    public function getStatusCode()
    {
        return 422;
    }

    /**
     * {@inheritdoc}
     */
    public function getErrors()
    {
        return array_map(function ($path, $detail) {
            $source = ['pointer' => '/data/attributes/' . $path];

            return compact('source', 'detail');
        }, array_keys($this->messages), $this->messages);
    }
}
