<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Ai\Features;

use InvalidArgumentException;

/**
 * Something an extension uses AI for, registered with
 * {@see \Flarum\Ai\Extend\Ai::feature()}. The admin turns it on, chooses its
 * connection and model, limits its tokens and decides who may use it.
 */
class Feature
{
    /**
     * Your extension's ID and a name, like "acme-tldr.summaries".
     */
    public const KEY_PATTERN = '/^[a-z0-9][a-z0-9_-]*(\.[a-z0-9_-]+)+$/';

    /**
     * Core's permissions column is 100 characters, and the key is prefixed
     * with "ai.feature." to make the feature's permission.
     */
    public const KEY_MAX_LENGTH = 100 - 11;

    public function __construct(
        public readonly string $key,
        /** A translation key for the feature's name. */
        public readonly string $label,
        /** A translation key for a sentence on what it does. */
        public readonly ?string $description,
        /** The ID of the extension that registered it. */
        public readonly ?string $extension
    ) {
        static::assertValidKey($key, $extension);
    }

    /**
     * The permission a member or guest needs to use the feature.
     */
    public function permission(): string
    {
        return 'ai.feature.'.$this->key;
    }

    /**
     * @throws InvalidArgumentException
     */
    public static function assertValidKey(string $key, ?string $extension = null): void
    {
        $from = $extension ? " (registered by $extension)" : '';

        if (! preg_match(self::KEY_PATTERN, $key)) {
            throw new InvalidArgumentException("\"$key\"$from is not a valid AI feature key. Use your extension's ID and a name, in lower case, like \"acme-tldr.summaries\".");
        }

        if (strlen($key) > self::KEY_MAX_LENGTH) {
            throw new InvalidArgumentException("The AI feature key \"$key\"$from is too long: keys are at most \".self::KEY_MAX_LENGTH.\" characters.");
        }
    }
}
