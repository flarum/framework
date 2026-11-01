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

    public static function requested(Request $request): bool
    {
        return strtolower(trim($request->getHeaderLine(self::HEADER))) === 'true';
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
