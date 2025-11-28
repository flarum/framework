<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Realtime\Websocket\Exception;

use Exception;

class WebsocketException extends Exception
{
    /**
     * Get the payload, Pusher-like formatted.
     *
     * @return array
     */
    public function getPayload()
    {
        return [
            'event' => 'pusher:error',
            'data' => [
                'message' => $this->getMessage(),
                'code' => $this->getCode(),
            ],
        ];
    }

    /**
     * Trigger the exception message.
     *
     * @param  string  $message
     * @param  int  $code
     * @return void
     */
    public function trigger(string $message, int $code = 4001)
    {
        $this->message = $message;
        $this->code = $code;
    }
}
