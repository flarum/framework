<?php

/**
 *
 */

namespace SychO\PackageManager\Extension\Event;

class Installed
{
    /**
     * @var string
     */
    public $extensionId;

    public function __construct(string $extensionId)
    {
        $this->extensionId = $extensionId;
    }
}
