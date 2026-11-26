<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Ideation\Tests\unit;

use Flarum\Ideation\GitHub\Config;
use Flarum\Ideation\GitHub\Gone;
use Flarum\Ideation\GitHub\HttpClient;
use Flarum\Ideation\GitHub\Reference;
use Flarum\Ideation\GitHub\Unavailable;
use Flarum\Settings\SettingsRepositoryInterface;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

class HttpClientTest extends TestCase
{
    /** The client, answered by $response instead of GitHub. */
    protected function client(ResponseInterface $response): HttpClient
    {
        $config = new Config($this->createStub(SettingsRepositoryInterface::class));

        return new class($config, $response) extends HttpClient {
            public function __construct(Config $config, protected ResponseInterface $response)
            {
                parent::__construct($config);
            }

            protected function get(string $path, array $headers): ResponseInterface
            {
                return $this->response;
            }
        };
    }

    protected function outcome(ResponseInterface $response): string
    {
        try {
            $item = $this->client($response)->fetch(new Reference('flarum/framework', 'issue', 1));
        } catch (Gone) {
            return 'gone';
        } catch (Unavailable) {
            return 'unavailable';
        }

        return $item === null ? 'missing' : $item['state'];
    }

    #[Test]
    public function one_item_that_moved_or_is_blocked_is_told_apart_from_github_being_unavailable()
    {
        $limits = ['x-ratelimit-remaining' => '4999'];

        $cases = [
            'found' => ['open', new Response(200, [], json_encode(['title' => 'Dark mode', 'state' => 'open']))],
            'not found' => ['missing', new Response(404)],
            'deleted' => ['missing', new Response(410)],
            'renamed repository' => ['gone', new Response(301, ['Location' => 'https://api.github.com/repositories/1/issues/1'] + $limits)],
            'transferred, temporary redirect' => ['gone', new Response(307, $limits)],
            'blocked for legal reasons' => ['gone', new Response(451, $limits)],
            'access blocked' => ['gone', new Response(403, $limits, '{"message":"Repository access blocked"}')],
            'rate limit spent' => ['unavailable', new Response(403, ['x-ratelimit-remaining' => '0'])],
            'secondary rate limit' => ['unavailable', new Response(403, ['retry-after' => '60'] + $limits)],
            'secondary rate limit, by message' => ['unavailable', new Response(403, $limits, '{"message":"You have exceeded a secondary rate limit."}')],
            'too many requests' => ['unavailable', new Response(429, $limits)],
            'server error' => ['unavailable', new Response(502)],
            'not JSON' => ['unavailable', new Response(200, [], 'oops')],
        ];

        foreach ($cases as $case => [$expected, $response]) {
            $this->assertSame($expected, $this->outcome($response), $case);
        }
    }
}
