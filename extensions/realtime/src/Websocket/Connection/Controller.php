<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Realtime\Websocket\Connection;

use Exception;
use Flarum\Realtime\Websocket\Channel\Manager;
use Flarum\Realtime\Websocket\Concerns\Settings;
use Flarum\Realtime\Websocket\Settings as SocketSettings;
use GuzzleHttp\Psr7\Message;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Laminas\Diactoros\Response;
use Laminas\Diactoros\Response\JsonResponse;
use Laminas\Diactoros\ServerRequest;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ServerRequestInterface;
use Pusher\Pusher;
use Ratchet\ConnectionInterface;
use Ratchet\Http\HttpServerInterface;
use WeakMap;

abstract class Controller implements HttpServerInterface
{
    use Settings;

    /**
     * The request being handled, loaded from {@see $pending} just before it is.
     */
    protected RequestInterface $request;
    protected int $contentLength = 0;
    protected string $buffer = '';

    /**
     * What each connection has sent so far, until its body is complete. One
     * controller serves every connection to its route, and a body can arrive
     * after its headers, so a request that started in the meantime must not
     * take the place of one that's still arriving.
     *
     * @var WeakMap<ConnectionInterface, array{request: RequestInterface, buffer: string, contentLength: int}>|null
     */
    private ?WeakMap $pending = null;

    public function __construct(protected Manager $manager, protected Pusher $pusher)
    {
    }

    public function onOpen(ConnectionInterface $conn, ?RequestInterface $request = null): void
    {
        // The HTTP server always passes the request; without one there's nothing to handle.
        if ($request === null) {
            return;
        }

        $this->pending()[$conn] = [
            'request' => $request,
            'buffer' => $request->getBody()->getContents(),
            'contentLength' => $this->contentLength($request->getHeaders()),
        ];

        $this->handleIfComplete($conn);
    }

    public function onMessage(ConnectionInterface $from, $msg): void
    {
        if (! isset($this->pending()[$from])) {
            return;
        }

        $state = $this->pending()[$from];
        $state['buffer'] .= $msg;
        $this->pending()[$from] = $state;

        $this->handleIfComplete($from);
    }

    public function onClose(ConnectionInterface $connection): void
    {
        unset($this->pending()[$connection]);
    }

    public function onError(ConnectionInterface $connection, Exception $exception): void
    {
        unset($this->pending()[$connection]);

        $response = new JsonResponse([
            'error' => $exception->getMessage()
        ], 400);

        tap($connection)->send(Message::toString($response))->close();
    }

    /**
     * Handle the connection's request once all of its body has arrived. It's
     * loaded onto the controller only now, and handled straight away, so
     * nothing else runs while it's there.
     */
    private function handleIfComplete(ConnectionInterface $conn): void
    {
        [
            'request' => $this->request,
            'buffer' => $this->buffer,
            'contentLength' => $this->contentLength,
        ] = $this->pending()[$conn];

        if (! $this->verifyContentLength()) {
            return;
        }

        unset($this->pending()[$conn]);

        $this->handleRequest($conn);
    }

    /**
     * @return WeakMap<ConnectionInterface, array{request: RequestInterface, buffer: string, contentLength: int}>
     */
    private function pending(): WeakMap
    {
        return $this->pending ??= new WeakMap();
    }

    protected function handleRequest(ConnectionInterface $conn): void
    {
        $query = [];
        parse_str($this->request->getUri()->getQuery(), $query);

        $request = (new ServerRequest(
            [],
            [],
            $this->request->getUri(),
            $this->request->getMethod(),
            $this->request->getBody(),
            $this->request->getHeaders()
        ))
            ->withQueryParams($query)
            ->withParsedBody(json_decode($this->buffer, true))
            /** @phpstan-ignore-next-line */
            ->withAttribute('socket_id', $conn->socketId ?? null);

        $this->validateSignature($request);

        $response = $this($request);

        // Allow for async IO in the controller action
        $response = \React\Promise\resolve($response);

        $response->then(function ($response) use ($conn) {
            if ($response instanceof Exception) {
                $this->sendAndClose($conn, new JsonResponse(['error' => $response->getMessage(), 'trace' => $response->getTraceAsString()], 400));

                return;
            }

            $this->sendAndClose($conn, $response);
        });
    }

    abstract public function __invoke(ServerRequestInterface $request): mixed;

    protected function contentLength(array $headers): int
    {
        return collect($headers)->first(function ($values, $header) {
            return \strtolower($header) === 'content-length';
        })[0] ?? 0;
    }

    protected function verifyContentLength(): bool
    {
        return strlen($this->buffer) === $this->contentLength;
    }

    protected function validateSignature(ServerRequest $request): void
    {
        $params = Arr::except($request->getQueryParams(), [
            'auth_signature', 'body_md5', 'appId', 'appKey', 'channelName',
        ]);

        if ($this->buffer !== '') {
            $params['body_md5'] = md5($this->buffer);
        }

        ksort($params);

        $signature = "{$request->getMethod()}\n{$request->getUri()->getPath()}\n".Pusher::array_implode('=', '&', $params);

        /** @var SocketSettings $settings */
        $settings = resolve(SocketSettings::class);

        $authSignature = hash_hmac('sha256', $signature, $settings->appSecret);

        if ($authSignature !== $request->getQueryParams()['auth_signature']) {
            throw new Exception('Invalid auth signature provided.');
        }
    }

    protected function sendAndClose(ConnectionInterface $conn, mixed $response): void
    {
        if ($response instanceof Collection) {
            $response = new JsonResponse($response->toArray());
        }
        if (is_array($response)) {
            $response = new JsonResponse($response);
        }
        if (! ($response instanceof Response)) {
            $response = new Response($response);
        }

        $conn->send(Message::toString($response));
        $conn->close();
    }
}
