<?php

namespace SychO\PackageManager\Event;

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
