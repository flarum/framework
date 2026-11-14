<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Http;

use Closure;
use Laminas\Diactoros\CallbackStream;
use Laminas\HttpHandlerRunner\Emitter\EmitterInterface;
use Laminas\HttpHandlerRunner\Emitter\SapiEmitter;
use Laminas\HttpHandlerRunner\Emitter\SapiStreamEmitter;
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
 *
 * Before streaming, PHP is told not to hold the body back itself: the output
 * buffers `output_buffering` opens are closed, so the callback's `flush()`
 * reaches the server, and `zlib.output_compression` is turned off, as it
 * would gather the body to compress it. {@see EventStream} builds such a
 * response.
 */
class Emitter implements EmitterInterface
{
    /**
     * @param (Closure(): void)|null $prepareToStream Run before a streamed response is sent; PHP's own preparation unless given.
     */
    public function __construct(
        private readonly EmitterInterface $buffered = new SapiEmitter(),
        private readonly EmitterInterface $streamed = new SapiStreamEmitter(),
        private readonly ?Closure $prepareToStream = null
    ) {
    }

    public function emit(ResponseInterface $response): bool
    {
        if (! $response->getBody() instanceof CallbackStream) {
            return $this->buffered->emit($response);
        }

        ($this->prepareToStream ?? self::prepareToStream(...))();

        return $this->streamed->emit($response);
    }

    /**
     * Stops PHP holding a streamed body back. A buffer that already holds
     * output is left alone, for the emitter to report as output sent too soon.
     */
    public static function prepareToStream(): void
    {
        if (! headers_sent()) {
            @ini_set('zlib.output_compression', '0');
        }

        // Apache's mod_deflate, which would also gather the body to compress it.
        if (function_exists('apache_setenv')) {
            @apache_setenv('no-gzip', '1');
        }

        while (ob_get_level() > 0 && ob_get_length() === 0 && @ob_end_clean()) {
            // Each empty buffer closed in turn, until one can't be or isn't empty.
        }
    }
}
