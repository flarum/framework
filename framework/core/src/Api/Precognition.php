<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Api;

use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * Precognition-style validate-only requests.
 *
 * A client sends the normal create (`POST /api/{type}`) or update
 * (`PATCH /api/{type}/{id}`) request with the header `Precognition: true`,
 * optionally limiting validation to some fields with
 * `Precognition-Validate-Only: name,email`. The endpoint authorizes and
 * validates exactly as it normally would, but never runs its hooks and never
 * saves: success answers `204` with `Precognition-Success: true`.
 *
 * Whether a request is handled precognitively is decided once, by
 * {@see Middleware\HandlePrecognitiveRequests}, which marks the request with
 * the {@see self::ATTRIBUTE} attribute. Everything downstream (the throttle
 * middleware, the endpoints) reads that attribute, never the raw header, so a
 * `Precognition` header sent to an endpoint that cannot honour it changes nothing.
 */
final class Precognition
{
    public const string HEADER = 'Precognition';
    public const string VALIDATE_ONLY_HEADER = 'Precognition-Validate-Only';
    public const string SUCCESS_HEADER = 'Precognition-Success';

    public const string ATTRIBUTE = 'precognitive';

    /**
     * Whether the request carries a non-empty `Precognition` header at all.
     * Any such header signals validate-only intent, so it must never fall
     * through to a normal (writing) request; see {@see self::wellFormed()}.
     */
    public static function requested(Request $request): bool
    {
        return trim($request->getHeaderLine(self::HEADER)) !== '';
    }

    /**
     * Whether every value of the `Precognition` header is `true` (trimmed,
     * case-insensitive). `true, true` from a duplicated header is accepted;
     * any other value (`1`, `yes`, `true, false`) is not.
     */
    public static function wellFormed(Request $request): bool
    {
        foreach (explode(',', $request->getHeaderLine(self::HEADER)) as $value) {
            if (strtolower(trim($value)) !== 'true') {
                return false;
            }
        }

        return true;
    }

    public static function active(Request $request): bool
    {
        return $request->getAttribute(self::ATTRIBUTE) === true;
    }

    /**
     * An absent or empty header validates every field.
     *
     * @return string[]|null the field names to validate, or null for all
     */
    public static function validateOnly(Request $request): ?array
    {
        $names = array_values(array_filter(
            array_map('trim', explode(',', $request->getHeaderLine(self::VALIDATE_ONLY_HEADER))),
            fn (string $name) => $name !== ''
        ));

        return $names ?: null;
    }
}
