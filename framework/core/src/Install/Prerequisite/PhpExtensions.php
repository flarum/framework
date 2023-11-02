<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Install\Prerequisite;

use Illuminate\Support\Collection;

class PhpExtensions implements PrerequisiteInterface
{
    public function __construct(
        protected array $extensions
    ) {
    }

    public function problems(): Collection
    {
        return (new Collection($this->extensions))
            ->reject(fn ($extension) => extension_loaded($extension))
            ->map(fn ($extension) => [
                'message' => "The PHP extension '$extension' is required.",
            ]);
    }
}
