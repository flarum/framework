<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\PackageManager\Event;

class FlarumUpdated
{
    public const GLOBAL = 'global';
    public const MAJOR = 'major';
    public const MINOR = 'minor';

    /**
     * @var string
     */
    public $type;

    public function __construct(string $type)
    {
        $this->type = $type;
    }
}
