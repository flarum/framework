<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\ExtensionManager\Composer;

class ComposerOutput
{
    /**
     * @var int
     */
    protected $exitCode;

    /**
     * @var string
     */
    protected $contents;

    public function __construct(int $exitCode, string $contents)
    {
        $this->exitCode = $exitCode;
        $this->contents = $contents;
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
