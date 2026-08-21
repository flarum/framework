<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Extension\Exception;

use RuntimeException;

class UnreadableManifestException extends RuntimeException
{
    public function __construct(
        public string $path,
        string $reason
    ) {
        parent::__construct("Cannot read the installed package manifest at $path: $reason. Flarum cannot determine which extensions are installed until this is resolved, which usually means completing or re-running `composer install`.");
    }

    public static function missing(string $path): self
    {
        return new self($path, 'the file does not exist');
    }

    public static function unparsable(string $path): self
    {
        return new self($path, 'the file is not valid JSON, so it may be corrupt or only partially written');
    }
}
