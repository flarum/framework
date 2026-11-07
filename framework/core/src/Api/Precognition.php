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
 * Validate-only requests: a create or update request sent with
 * `Precognition: true` (optionally `Precognition-Validate-Only: name,email`)
 * is authorized and validated but never saved, and answers `204` with
 * `Precognition-Success: true`.
 *
 * Only {@see Middleware\HandlePrecognitiveRequests} reads the header; everything
 * downstream checks the {@see self::ATTRIBUTE} attribute it sets.
 */
final class Precognition
{
    public const string HEADER = 'Precognition';
    public const string VALIDATE_ONLY_HEADER = 'Precognition-Validate-Only';
    public const string SUCCESS_HEADER = 'Precognition-Success';

    public const string ATTRIBUTE = 'precognitive';

    /**
     * Any non-empty value signals validate-only intent, so it must never fall
     * through to a normal (writing) request; see {@see self::wellFormed()}.
     */
    public static function requested(Request $request): bool
    {
        return trim($request->getHeaderLine(self::HEADER)) !== '';
    }

    /**
     * Every value must be `true`, so a duplicated header (`true, true`) passes.
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
     * @return string[]|null null validates every field
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
