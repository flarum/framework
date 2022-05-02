<?php

namespace Flarum\PackageManager\Command;

interface BusinessCommandInterface
{
    public function getOperationName(): string;
}
