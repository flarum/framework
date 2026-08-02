<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Akismet;

use RuntimeException;

/**
 * Thrown when Akismet answers something other than a spam verdict — most
 * commonly the literal body "invalid" for a misconfigured key, accompanied by
 * an X-akismet-debug-help header explaining why. Callers treat this like any
 * other Akismet failure: log it and fail open.
 */
class AkismetUnexpectedResponseException extends RuntimeException
{
    public function __construct(
        public readonly string $body,
        string $debugHelp = ''
    ) {
        parent::__construct(
            $debugHelp !== ''
                ? $debugHelp
                : "Unexpected Akismet response: $body"
        );
    }
}
