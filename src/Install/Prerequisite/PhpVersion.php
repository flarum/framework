<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
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
        $collection = new Collection;

        if (version_compare(PHP_VERSION, $this->minVersion, '<')) {
            $collection->push([
                'message' => "PHP $this->minVersion is required.",
                'detail' => 'You are running version '.PHP_VERSION.'. You might want to talk to your system administrator about upgrading to the latest PHP version.',
            ]);
        }

        return $collection;
    }
}
