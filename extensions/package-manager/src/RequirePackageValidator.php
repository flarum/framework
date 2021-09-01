<?php

namespace SychO\PackageManager;

use Flarum\Foundation\AbstractValidator;

class RequirePackageValidator extends AbstractValidator
{
    /**
     * {@inheritdoc}
     */
    protected $rules = [
        'package' => 'required|string'
    ];
}
