<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Akismet\Tests\unit;

use Flarum\Akismet\Akismet;
use Flarum\Akismet\AkismetUnexpectedResponseException;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class AkismetTest extends TestCase
{
    /** @var array<int, array{request: Request, options: array}> */
    private array $history = [];

    private function akismet(array $responses, string $apiKey = 'test-key', bool $debug = false): Akismet
    {
        $this->history = [];

        $mock = new MockHandler($responses);
        $stack = HandlerStack::create($mock);
        $stack->push(Middleware::history($this->history));

        return new Akismet(
            $apiKey,
            'https://forum.example.com',
            '2.0.0',
            '1.0.0',
            $debug,
            new Client(['handler' => $stack])
        );
    }

    private function lastRequest(): Request
    {
        return $this->history[count($this->history) - 1]['request'];
    }

    private function lastRequestParams(): array
    {
        parse_str((string) $this->lastRequest()->getBody(), $params);

        return $params;
    }

    #[Test]
    public function requests_go_to_the_modern_endpoint_with_the_key_as_a_parameter()
    {
        // Akismet's current API form: POST to rest.akismet.com with api_key in
        // the body. The legacy {key}.rest.akismet.com subdomain form leaked the
        // secret key into DNS lookups and TLS SNI on every request.
        $this->akismet([new Response(200, [], 'false')])->checkSpam();

        $uri = $this->lastRequest()->getUri();

        $this->assertSame('rest.akismet.com', $uri->getHost());
        $this->assertSame('/1.1/comment-check', $uri->getPath());
        $this->assertSame('test-key', $this->lastRequestParams()['api_key']);
    }

    #[Test]
    public function the_blog_url_and_user_agent_are_always_sent()
    {
        $this->akismet([new Response(200, [], 'false')])->checkSpam();

        $this->assertSame('https://forum.example.com', $this->lastRequestParams()['blog']);
        $this->assertStringContainsString('Flarum/2.0.0', $this->lastRequest()->getHeaderLine('User-Agent'));
    }

    #[Test]
    public function check_spam_reports_spam_and_the_discard_pro_tip()
    {
        $result = $this->akismet([
            new Response(200, ['X-akismet-pro-tip' => 'discard'], 'true'),
        ])->checkSpam();

        $this->assertTrue($result['isSpam']);
        $this->assertSame('discard', $result['proTip']);
    }

    #[Test]
    public function check_spam_reports_ham()
    {
        $result = $this->akismet([new Response(200, [], 'false')])->checkSpam();

        $this->assertFalse($result['isSpam']);
    }

    #[Test]
    public function an_unexpected_response_throws_with_the_debug_help()
    {
        // A misconfigured key returns literally "invalid" plus a debug header.
        // The old client compared the body to 'true' and silently treated this
        // as ham — the forum ran unprotected and nobody ever found out.
        $this->expectException(AkismetUnexpectedResponseException::class);
        $this->expectExceptionMessage('Missing required field: api_key.');

        $this->akismet([
            new Response(200, ['X-akismet-debug-help' => 'Missing required field: api_key.'], 'invalid'),
        ])->checkSpam();
    }

    #[Test]
    public function verify_key_accepts_a_valid_key()
    {
        $akismet = $this->akismet([new Response(200, [], 'valid')]);

        $this->assertTrue($akismet->verifyKey());
        $this->assertSame('/1.1/verify-key', $this->lastRequest()->getUri()->getPath());
        $this->assertSame('test-key', $this->lastRequestParams()['api_key']);
    }

    #[Test]
    public function verify_key_rejects_an_invalid_key()
    {
        $this->assertFalse($this->akismet([new Response(200, [], 'invalid')])->verifyKey());
    }

    #[Test]
    public function verify_key_can_check_a_candidate_key_before_it_is_saved()
    {
        $akismet = $this->akismet([new Response(200, [], 'valid')]);

        $this->assertTrue($akismet->verifyKey('candidate-key'));
        $this->assertSame('candidate-key', $this->lastRequestParams()['api_key']);
    }

    #[Test]
    public function requests_carry_a_timeout_so_a_hanging_akismet_cannot_hang_the_forum()
    {
        $this->akismet([new Response(200, [], 'false')])->checkSpam();

        $options = $this->history[0]['options'];

        $this->assertArrayHasKey('timeout', $options);
        $this->assertLessThanOrEqual(5, $options['timeout']);
    }

    #[Test]
    public function network_failures_bubble_as_guzzle_exceptions_for_callers_to_fail_open_on()
    {
        $this->expectException(ConnectException::class);

        $this->akismet([
            new ConnectException('Connection refused', new Request('POST', 'comment-check')),
        ])->checkSpam();
    }

    #[Test]
    public function debug_mode_marks_requests_as_tests()
    {
        $this->akismet([new Response(200, [], 'false')], debug: true)->checkSpam();

        $this->assertSame('1', $this->lastRequestParams()['is_test']);
    }

    #[Test]
    public function with_param_is_immutable()
    {
        $akismet = $this->akismet([new Response(200, [], 'false'), new Response(200, [], 'false')]);

        $akismet->withContent('spam content')->checkSpam();
        $withoutContent = $this->lastRequestParams();

        $this->assertSame('spam content', $withoutContent['comment_content'] ?? null);

        $akismet->checkSpam();

        $this->assertArrayNotHasKey('comment_content', $this->lastRequestParams(), 'withContent must not mutate the original instance');
    }

    #[Test]
    public function is_configured_reflects_the_presence_of_a_key()
    {
        $this->assertTrue($this->akismet([])->isConfigured());
        $this->assertFalse($this->akismet([], apiKey: '')->isConfigured());
    }
}
