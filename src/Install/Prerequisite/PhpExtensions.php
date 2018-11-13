<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Install\Prerequisite;

use Illuminate\Support\Collection;

class PhpExtensions implements PrerequisiteInterface
{
    protected $extensions;

    public function __construct(array $extensions)
    {
        $this->extensions = $extensions;
    }

    public function problems(): Collection
    {
        return collect($this->extensions)
            ->reject(function ($extension) {
                return extension_loaded($extension);
            })->map(function ($extension) {
                return [
                    'message' => "The PHP extension '$extension' is required.",
                ];
            });
    }
}
