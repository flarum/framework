<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\ExtensionManager;

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
