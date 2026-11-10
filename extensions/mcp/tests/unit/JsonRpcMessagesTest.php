<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Mcp\Tests\unit;

use Flarum\Mcp\Http\JsonRpcMessages;
use Laminas\Diactoros\ServerRequest;
use Laminas\Diactoros\StreamFactory;
use Mcp\Server\Transport\StreamableHttpTransport;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\StreamInterface;

class JsonRpcMessagesTest extends TestCase
{
    #[Test]
    public function a_body_of_unknown_size_is_read_up_to_the_limit_only(): void
    {
        $limit = StreamableHttpTransport::DEFAULT_MAX_BODY_BYTES;
        $body = $this->unsizedBody(json_encode([
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'tools/call',
            'params' => ['name' => 'whoami', 'padding' => str_repeat('a', $limit)],
        ], JSON_THROW_ON_ERROR));

        $messages = JsonRpcMessages::fromRequest($this->post($body));

        $this->assertSame([], $messages->messages);
        $this->assertLessThanOrEqual($limit + 1, $body->bytesRead);
        $this->assertSame(0, $body->tell());
    }

    #[Test]
    public function a_body_of_unknown_size_within_the_limit_is_read(): void
    {
        $body = $this->unsizedBody('{"jsonrpc":"2.0","id":1,"method":"tools/call","params":{"name":"whoami"}}');

        $messages = JsonRpcMessages::fromRequest($this->post($body));

        $this->assertSame(['whoami'], $messages->toolCalls());
        $this->assertSame(0, $body->tell());
    }

    protected function post(StreamInterface $body): ServerRequest
    {
        return new ServerRequest([], [], '/mcp', 'POST', $body);
    }

    /**
     * A seekable body that does not know its size (as a chunked upload may
     * not), counting the bytes read from it.
     *
     * @return StreamInterface&object{bytesRead: int}
     */
    protected function unsizedBody(string $contents): StreamInterface
    {
        $inner = (new StreamFactory())->createStream($contents);
        $inner->rewind();

        return new class($inner) implements StreamInterface {
            public int $bytesRead = 0;

            public function __construct(protected StreamInterface $inner)
            {
            }

            public function __toString(): string
            {
                $this->rewind();

                return $this->getContents();
            }

            public function close(): void
            {
                $this->inner->close();
            }

            public function detach()
            {
                return $this->inner->detach();
            }

            public function getSize(): ?int
            {
                return null;
            }

            public function tell(): int
            {
                return $this->inner->tell();
            }

            public function eof(): bool
            {
                return $this->inner->eof();
            }

            public function isSeekable(): bool
            {
                return true;
            }

            public function seek(int $offset, int $whence = SEEK_SET): void
            {
                $this->inner->seek($offset, $whence);
            }

            public function rewind(): void
            {
                $this->inner->rewind();
            }

            public function isWritable(): bool
            {
                return false;
            }

            public function write(string $string): int
            {
                throw new \RuntimeException('Not writable.');
            }

            public function isReadable(): bool
            {
                return true;
            }

            public function read(int $length): string
            {
                $read = $this->inner->read($length);
                $this->bytesRead += strlen($read);

                return $read;
            }

            public function getContents(): string
            {
                $contents = $this->inner->getContents();
                $this->bytesRead += strlen($contents);

                return $contents;
            }

            public function getMetadata(?string $key = null)
            {
                return $this->inner->getMetadata($key);
            }
        };
    }
}
