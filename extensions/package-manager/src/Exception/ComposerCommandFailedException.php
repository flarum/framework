<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\PackageManager\Exception;

use Exception;

class ComposerCommandFailedException extends Exception
{
    /**
     * @var string
     */
    public $packageName;

    public function __construct(string $packageName, string $output)
    {
        $this->packageName = $packageName;

        parent::__construct($output);
    }
}
