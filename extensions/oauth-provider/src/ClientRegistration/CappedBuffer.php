<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\OAuthProvider\ClientRegistration;

use GuzzleHttp\Psr7\StreamDecoratorTrait;
use GuzzleHttp\Psr7\Utils;
use Psr\Http\Message\StreamInterface;

/**
 * In-memory response sink that refuses to grow past a byte limit. A refused
 * write returns 0, which makes cURL abort the transfer (CURLE_WRITE_ERROR)
 * instead of downloading the rest of the body.
 */
class CappedBuffer implements StreamInterface
{
    use StreamDecoratorTrait;

    /** @var StreamInterface */
    protected $stream;

    protected bool $exceeded = false;

    public function __construct(protected int $maxBytes)
    {
        $this->stream = Utils::streamFor('');
    }

    public function write($string): int
    {
        $string = (string) $string;

        if ($this->exceeded || ($this->stream->getSize() ?? 0) + strlen($string) > $this->maxBytes) {
            $this->exceeded = true;

            return 0;
        }

        return $this->stream->write($string);
    }

    public function exceeded(): bool
    {
        return $this->exceeded;
    }
}
