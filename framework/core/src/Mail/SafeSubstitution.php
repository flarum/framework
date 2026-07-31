<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Mail;

/**
 * Carries values through a formatter without letting them be parsed.
 *
 * Email bodies are translation strings containing markup — a markdown link
 * whose text is a discussion title, say — that get rendered by the formatter
 * after their parameters are substituted in. Substituting first means the
 * parser sees the values: a title can close the link and choose its own
 * destination, or add an image that loads when the mail is opened.
 *
 * Values are therefore replaced with an opaque marker before rendering, and
 * put back — HTML-escaped — afterwards. The marker is deliberately plain
 * (letters and digits only) so that neither markdown parsing nor URL encoding
 * alters it, and it encodes the value inline rather than in shared state, so
 * that repeated or nested renders in one request cannot mix values up.
 */
abstract class SafeSubstitution
{
    private const PREFIX = 'flarumsafevalue';
    private const SUFFIX = 'endflarumsafevalue';

    /**
     * Replace each parameter value with a marker that survives rendering.
     *
     * @param array<string, mixed> $parameters
     * @return array<string, string>
     */
    public static function mark(array $parameters): array
    {
        return array_map(
            fn (mixed $value) => self::PREFIX.self::encode((string) $value).self::SUFFIX,
            $parameters
        );
    }

    /**
     * Put the marked values back into rendered output, escaped so that they
     * are shown rather than interpreted.
     */
    public static function restore(string $rendered): string
    {
        return preg_replace_callback(
            '/'.self::PREFIX.'([A-Za-z0-9]*)'.self::SUFFIX.'/',
            fn (array $matches) => htmlspecialchars(self::decode($matches[1]), ENT_QUOTES, 'UTF-8'),
            $rendered
        ) ?? $rendered;
    }

    /**
     * Whether rendered output still holds marked values.
     */
    public static function contains(string $rendered): bool
    {
        return str_contains($rendered, self::PREFIX);
    }

    /**
     * Base16, so the encoded form is letters and digits only: base64 uses
     * characters that markdown and URL encoding would alter.
     */
    private static function encode(string $value): string
    {
        return bin2hex($value);
    }

    private static function decode(string $encoded): string
    {
        // An odd length can only mean the marker was mangled after all; return
        // nothing rather than a broken byte sequence.
        if ($encoded === '' || strlen($encoded) % 2 !== 0) {
            return '';
        }

        return hex2bin($encoded) ?: '';
    }
}
