<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\PackageManager;

use Flarum\Foundation\AbstractValidator;

class RequirePackageValidator extends AbstractValidator
{
    /**
     * {@inheritdoc}
     */
    protected $rules = [
        'package' => ['required', 'string', 'regex:/^[A-z0-9-_]+\/[A-z-0-9]+(?::[A-z-0-9.->=<_]+){0,1}$/i']
    ];
}
