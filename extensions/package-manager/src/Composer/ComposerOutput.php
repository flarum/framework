<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\PackageManager\Composer;

class ComposerOutput
{
    public function __construct(
        protected int $exitCode,
        protected string $contents
    ) {
    }

    public function getExitCode(): int
    {
        return $this->exitCode;
    }

    public function getContents(): string
    {
        return $this->contents;
    }
}
