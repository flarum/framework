<?php

namespace Flarum\PackageManager;

use Flarum\Foundation\AbstractValidator;

class WhyNotValidator extends AbstractValidator
{
    /**
     * {@inheritdoc}
     */
    protected $rules = [
        'package' => ['required', 'string', 'regex:'.RequirePackageValidator::PACKAGE_NAME_REGEX],
        'version' => ['sometimes', 'string', 'regex:/(?:\*|[A-z0-9.-]+)/i']
    ];
}
