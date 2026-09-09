<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Http;

use Laminas\Diactoros\CallbackStream;
use Laminas\HttpHandlerRunner\Emitter\SapiEmitter;
use Laminas\HttpHandlerRunner\Emitter\SapiStreamEmitter;
use Laminas\HttpHandlerRunner\Emitter\EmitterInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Sends a response, streaming it only where streaming is what was asked for.
 *
 * Nearly everything Flarum returns is a complete string that should be sent in
 * one go, which is what {@see SapiEmitter} does. A handful of things — clearing
 * the cache, which recompiles every asset and can run for half a minute — need
 * to reach the browser as they happen, and for those the body is a
 * {@see CallbackStream} whose callback writes and flushes progressively.
 *
 * The distinction is drawn on the body rather than a flag, because
 * `SapiEmitter` would defeat a callback body anyway: it casts the stream to a
 * string, which runs the callback to completion before a single byte is sent.
 */
class Emitter implements EmitterInterface
{
    public function __construct(
        private readonly EmitterInterface $buffered = new SapiEmitter(),
        private readonly EmitterInterface $streamed = new SapiStreamEmitter()
    ) {
    }

    public function emit(ResponseInterface $response): bool
    {
        return $response->getBody() instanceof CallbackStream
            ? $this->streamed->emit($response)
            : $this->buffered->emit($response);
    }
}
