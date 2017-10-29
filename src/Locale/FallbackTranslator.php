<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Locale;

use Symfony\Component\Translation\TranslatorInterface;

/**
 * This is the fallback translator for the installation.
 * It contains only the basic translations needed for the validation.
 */
class FallbackTranslator implements TranslatorInterface
{
    /**
     * An array of the translations.
     *
     * @var array
     */
    protected $translations;

    /**
     * FallbackTranslator constructor.
     */
    public function __construct()
    {
        $this->translations = $this->getTranslations();
    }

    protected function getTranslations()
    {
        return [
            'validation' => [
                'alpha_dash' => 'The :attribute may only contain letters, numbers, and dashes.',
                'in' => 'The selected :attribute is invalid.',
                'in_array' => 'The :attribute field does not exist in :other.',
                'integer' => 'The :attribute must be an integer.',
                'max' => [
                    'numeric' => 'The :attribute may not be greater than :max.',
                    'string' => 'The :attribute may not be greater than :max characters.'
                ],
                'min' => [
                    'numeric' => 'The :attribute must be at least :min.'
                ],
                'required' => 'The :attribute field is required.',
                'string' => 'The :attribute must be a string.',
            ]
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function trans($id, array $parameters = [], $domain = null, $locale = null)
    {
        $translation = array_get($this->translations, $id, $id);

        return $translation;
    }

    /**
     * {@inheritdoc}
     */
    public function transChoice($id, $number, array $parameters = [], $domain = null, $locale = null)
    {
        return $this->trans($id, $parameters, $domain, $locale);
    }

    /**
     * {@inheritdoc}
     */
    public function setLocale($locale)
    {
        // We do nothing because this fallback translator only supports english.
    }

    /**
     * {@inheritdoc}
     */
    public function getLocale()
    {
        return 'en';
    }
}
