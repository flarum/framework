<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\api;

use Flarum\Api\Controller\CheckStreamingController;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * The dashboard asks for a few events a moment apart and times their arrival,
 * to warn when the server holds streamed responses back.
 */
class StreamingCheckTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->prepareDatabase(['users' => [$this->normalUser()]]);
    }

    #[Test]
    public function the_events_are_spread_out_on_the_server()
    {
        $response = $this->send($this->request('GET', '/api/streaming-check', ['authenticatedAs' => 1]));

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('no', $response->getHeaderLine('X-Accel-Buffering'));

        // Reading the body runs the check, as sending the response would.
        ob_start();
        $response->getBody()->getContents();
        $events = array_map(fn ($line) => json_decode($line, true), explode("\n", trim((string) ob_get_clean())));

        $this->assertSame(['start', 'tick', 'tick', 'tick', 'tick', 'done'], array_column($events, 'type'));

        // Far enough apart that held-back events would arrive together.
        $span = CheckStreamingController::TICKS * CheckStreamingController::TICK_MS;
        $this->assertGreaterThanOrEqual($span, end($events)['ms'] - $events[0]['ms']);
    }

    #[Test]
    public function only_admins_can_run_it()
    {
        $this->assertSame(403, $this->send($this->request('GET', '/api/streaming-check', ['authenticatedAs' => 2]))->getStatusCode());
        $this->assertSame(403, $this->send($this->request('GET', '/api/streaming-check'))->getStatusCode());
    }
}
