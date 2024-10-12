<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tags;

use Flarum\Foundation\AbstractValidator;

class TagValidator extends AbstractValidator
{
    /**
     * {@inheritdoc}
     */
    protected $rules = [
        'name' => ['required'],
        'slug' => ['required', 'unique:tags', 'regex:/^[^\/\\ ]*$/i'],
        'is_hidden' => ['bool'],
        'description' => ['string', 'max:700'],
        'color' => ['regex:/^#([a-f0-9]{6}|[a-f0-9]{3})$/i'],
    ];

    /**
     * {@inheritdoc}
     */
    protected function attributes()
    {
        return [
            'name' => $this->translator->trans('validation.attributes.name'),
            'slug' => $this->translator->trans('validation.attributes.slug'),
            'is_hidden' => $this->translator->trans('validation.attributes.is_hidden'),
            'description' => $this->translator->trans('validation.attributes.description'),
            'color' => $this->translator->trans('validation.attributes.color'),
        ];
    }
}
