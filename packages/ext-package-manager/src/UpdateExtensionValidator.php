<?php

namespace SychO\PackageManager;

use Flarum\Foundation\AbstractValidator;

class UpdateExtensionValidator extends AbstractValidator
{
    /**
     * {@inheritdoc}
     */
    protected $rules = [
        'extensionId' => 'required|string'
    ];
}
