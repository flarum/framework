<?php

namespace SychO\PackageManager\Extension\Event;

use Flarum\Extension\Extension;

class Installed
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
