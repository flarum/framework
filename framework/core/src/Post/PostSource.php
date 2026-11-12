<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Post;

use InvalidArgumentException;

/**
 * How a post reached the forum when it was not written in the forum's own
 * interface: through the API, an MCP client, or a channel an extension adds.
 *
 * Put it on the request with {@see \Flarum\Http\RequestUtil::withPostSource()}
 * and posts created during that request record it. The forum shows it next to
 * the author as "via <name>".
 */
final readonly class PostSource
{
    /**
     * Posts made through the REST API with an API key or access token.
     */
    public const string API = 'api';

    public const int MAX_KEY_LENGTH = 50;
    public const int MAX_NAME_LENGTH = 100;

    /**
     * @param string $key A short, lowercase identifier that search filters on,
     *                    such as `api` or `mcp`.
     * @param string|null $name What the forum shows after "via", when the key's
     *                          own label is not enough (e.g. an application's
     *                          name). Kept as it was when the post was made.
     */
    public function __construct(
        public string $key,
        public ?string $name = null,
    ) {
        if (! preg_match('/^[a-z0-9][a-z0-9_-]*$/D', $key) || strlen($key) > self::MAX_KEY_LENGTH) {
            throw new InvalidArgumentException("Invalid post source key [$key].");
        }
    }

    /**
     * The name as stored: trimmed, null when blank, and cut to the column's length.
     */
    public function storedName(): ?string
    {
        $name = trim((string) $this->name);

        return $name === '' ? null : mb_substr($name, 0, self::MAX_NAME_LENGTH);
    }
}
