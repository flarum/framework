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

class PhpVersion implements PrerequisiteInterface
{
    protected $minVersion;

    public function __construct($minVersion)
    {
        $this->minVersion = $minVersion;
    }

    public function problems(): Collection
    {
        if (version_compare(PHP_VERSION, $this->minVersion, '<')) {
            return collect()->push([
                'message' => "PHP $this->minVersion is required.",
                'detail' => 'You are running version '.PHP_VERSION.'. Talk to your hosting provider about upgrading to the latest PHP version.',
            ]);
        }

        return collect();
    }
}
