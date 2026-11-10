<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Install\Prerequisite;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

/**
 * At least one of the given PHP extensions must be loaded, such as the PDO
 * driver for any one of the supported databases.
 */
class AnyPhpExtension implements PrerequisiteInterface
{
    public function __construct(
        protected array $extensions
    ) {
    }

    public function problems(): Collection
    {
        foreach ($this->extensions as $extension) {
            if (extension_loaded($extension)) {
                return new Collection;
            }
        }

        $names = Arr::join(array_map(fn (string $extension) => "'$extension'", $this->extensions), ', ', ' or ');

        return new Collection([
            ['message' => "One of the PHP extensions $names is required."],
        ]);
    }
}
