<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\ExtensionManager\Tests\unit;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Promise\Create;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\RequestInterface;

/**
 * A Guzzle client that answers from a callable, so a test can describe the
 * network as url => body and nothing is ever sent. A null body is a host that
 * could not be reached.
 */
final class FakeHttp
{
    /** @param callable(string):?string $answer */
    public static function client(callable $answer): Client
    {
        return new Client(['handler' => function (RequestInterface $request) use ($answer): PromiseInterface {
            $body = $answer((string) $request->getUri());

            return $body === null
                ? Create::rejectionFor(new ConnectException('Could not reach '.$request->getUri()->getHost(), $request))
                : Create::promiseFor(new Response(200, [], $body));
        }]);
    }
}
