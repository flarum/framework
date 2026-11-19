<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\unit\Http;

use Flarum\Http\Emitter;
use Flarum\Testing\unit\TestCase;
use Laminas\Diactoros\CallbackStream;
use Laminas\Diactoros\Response;
use Laminas\HttpHandlerRunner\Emitter\EmitterInterface;
use Mockery as m;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ResponseInterface;

class EmitterTest extends TestCase
{
    /**
     * Counts the times PHP would have been prepared to stream, which would
     * otherwise close PHPUnit's own output buffer.
     */
    private int $prepared = 0;

    private function emitter(&$sentTo): Emitter
    {
        $record = function (string $which) use (&$sentTo) {
            $emitter = m::mock(EmitterInterface::class);
            $emitter->shouldReceive('emit')->andReturnUsing(function () use (&$sentTo, $which) {
                $sentTo = $which;

                return true;
            });

            return $emitter;
        };

        return new Emitter($record('buffered'), $record('streamed'), fn () => $this->prepared++);
    }

    /**
     * Everything Flarum returns other than the few progressive responses is a
     * complete string, and must keep being sent in one piece: the streaming
     * emitter handles content-length and ranges differently, so routing normal
     * responses through it would change every page on the site.
     */
    #[Test]
    public function an_ordinary_response_is_sent_in_one_piece()
    {
        $sentTo = null;

        $this->emitter($sentTo)->emit(new Response());

        $this->assertSame('buffered', $sentTo);
    }

    #[Test]
    public function a_response_with_a_string_body_is_sent_in_one_piece()
    {
        $sentTo = null;

        $response = new Response();
        $response->getBody()->write('{"data":[]}');

        $this->emitter($sentTo)->emit($response);

        $this->assertSame('buffered', $sentTo);
    }

    /**
     * A callback body is the request to stream. It has to be: the buffered
     * emitter casts the body to a string, which would run the callback to
     * completion before anything reached the browser — the opposite of what a
     * caller asked for by using one.
     */
    #[Test]
    public function a_callback_body_is_streamed()
    {
        $sentTo = null;

        $response = (new Response())->withBody(new CallbackStream(fn () => ''));

        $this->emitter($sentTo)->emit($response);

        $this->assertSame('streamed', $sentTo);
    }

    /**
     * PHP's own output buffer, and its compression, would hold a streamed body
     * back just as a proxy would.
     */
    #[Test]
    public function php_is_prepared_before_a_streamed_response_only()
    {
        $sentTo = null;

        $this->emitter($sentTo)->emit(new Response());
        $this->assertSame(0, $this->prepared);

        $this->emitter($sentTo)->emit((new Response())->withBody(new CallbackStream(fn () => '')));
        $this->assertSame(1, $this->prepared);
    }

    /**
     * Output already written is left in its buffer, for the streaming emitter
     * to refuse, rather than sent ahead of the headers.
     */
    #[Test]
    public function preparing_leaves_a_buffer_with_output_alone()
    {
        $level = ob_get_level();
        ob_start();
        echo 'Too soon';

        Emitter::prepareToStream();

        $this->assertSame($level + 1, ob_get_level());
        $this->assertSame('Too soon', ob_get_clean());
    }

    #[Test]
    public function preparing_closes_empty_buffers()
    {
        $level = ob_get_level();
        // One with output, standing in for PHPUnit's, so preparing stops there.
        ob_start();
        echo 'x';
        ob_start();
        ob_start();

        Emitter::prepareToStream();

        $this->assertSame($level + 1, ob_get_level());
        $this->assertSame('x', ob_get_clean());
    }

    #[Test]
    public function it_passes_the_response_through_untouched()
    {
        $response = (new Response())->withStatus(418)->withHeader('X-Test', 'yes');

        $seen = null;

        $buffered = m::mock(EmitterInterface::class);
        $buffered->shouldReceive('emit')->once()->andReturnUsing(function (ResponseInterface $r) use (&$seen) {
            $seen = $r;

            return true;
        });

        (new Emitter($buffered, m::mock(EmitterInterface::class), fn () => null))->emit($response);

        $this->assertSame($response, $seen);
    }

    #[Test]
    public function it_reports_what_the_underlying_emitter_reported()
    {
        $failing = m::mock(EmitterInterface::class);
        $failing->shouldReceive('emit')->andReturn(false);

        $this->assertFalse(
            (new Emitter($failing, m::mock(EmitterInterface::class), fn () => null))->emit(new Response())
        );
    }
}
