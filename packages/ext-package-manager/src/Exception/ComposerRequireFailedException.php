<?php

/**
 *
 */

namespace SychO\PackageManager\Exception;

use Exception;

class ComposerRequireFailedException extends Exception
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
