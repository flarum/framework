<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\ExtensionManager;

use Flarum\Foundation\AbstractValidator;

class UpdateExtensionValidator extends AbstractValidator
{
    /**
     * {@inheritdoc}
     */
    protected $rules = [
        'extensionId' => 'required|string',
        'updateMode' => 'required|in:soft,hard',
    ];

    /**
     * {@inheritdoc}
     */
    protected function attributes()
    {
        return [
            'extensionId' => $this->translator->trans('flarum-extension-manager.validation.attributes.extension_id'),
            'updateMode' => $this->translator->trans('flarum-extension-manager.validation.attributes.update_mode')
        ];
    }
}
