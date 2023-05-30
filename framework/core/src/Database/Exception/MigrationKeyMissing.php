<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Database\Exception;

use Exception;

class MigrationKeyMissing extends Exception
{
    public function __construct(protected string $direction, string $file = null)
    {
        $fileNameWithSpace = $file ? ' '.realpath($file) : '';
        parent::__construct("Migration file $fileNameWithSpace should contain an array with up/down (looking for $direction)");
    }

    public function withFile(string $file = null): self
    {
        return new self($this->direction, $file);
    }
}
