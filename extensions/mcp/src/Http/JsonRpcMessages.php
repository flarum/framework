<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Mcp\Http;

use Mcp\Server\Transport\StreamableHttpTransport;
use Psr\Http\Message\ServerRequestInterface;

/**
 * A peek at the JSON-RPC messages of a POST to /mcp, single or batch, without
 * consuming the body: the transport reads and validates it again.
 *
 * @internal
 */
class JsonRpcMessages
{
    /**
     * @param list<array<string, mixed>> $messages
     */
    public function __construct(
        public readonly array $messages,
    ) {
    }

    public static function fromRequest(ServerRequestInterface $request): self
    {
        if ($request->getMethod() !== 'POST') {
            return new self([]);
        }

        $body = $request->getBody();
        $size = $body->getSize();

        // An oversized body is refused by the transport before anything runs.
        if ($size !== null && $size > StreamableHttpTransport::DEFAULT_MAX_BODY_BYTES) {
            return new self([]);
        }

        $decoded = json_decode((string) $body, true);

        if ($body->isSeekable()) {
            $body->rewind();
        }

        if (! is_array($decoded)) {
            return new self([]);
        }

        $messages = [];
        foreach (array_is_list($decoded) ? $decoded : [$decoded] as $message) {
            if (is_array($message)) {
                $messages[] = $message;
            }
        }

        return new self($messages);
    }

    public function has(string $method): bool
    {
        foreach ($this->messages as $message) {
            if (($message['method'] ?? null) === $method) {
                return true;
            }
        }

        return false;
    }

    /**
     * The names of the tools called.
     *
     * @return list<string>
     */
    public function toolCalls(): array
    {
        $names = [];

        foreach ($this->messages as $message) {
            $name = ($message['method'] ?? null) === 'tools/call' ? ($message['params']['name'] ?? null) : null;

            if (is_string($name)) {
                $names[] = $name;
            }
        }

        return $names;
    }
}
