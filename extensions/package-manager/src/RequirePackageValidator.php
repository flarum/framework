<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\ExtensionManager;

use Flarum\Foundation\AbstractValidator;

class RequirePackageValidator extends AbstractValidator
{
    public const PACKAGE_NAME_REGEX = '/^[A-z0-9-_]+\/[A-z-0-9]+(?::[A-z-0-9.->=<_@"*]+){0,1}$/i';

    /**
     * {@inheritdoc}
     */
    protected $rules = [
        'package' => ['required', 'string', 'regex:'.self::PACKAGE_NAME_REGEX]
    ];
}
