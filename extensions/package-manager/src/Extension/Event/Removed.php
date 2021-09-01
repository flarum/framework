<?php

namespace SychO\PackageManager\Extension\Event;

use Flarum\Extension\Extension;

class Removed
{
    /**
     * @var Extension
     */
    public $extension;

    public function __construct(Extension $extension)
    {
        $this->extension = $extension;
    }
}
