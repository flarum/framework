<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Suspend;

use Flarum\Foundation\AbstractValidator;

class SuspendValidator extends AbstractValidator
{
    /**
     * {@inheritdoc}
     */
    protected $rules = [
        'suspendedUntil' => ['nullable', 'date'],
    ];
}
