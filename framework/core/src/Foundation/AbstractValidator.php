<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Foundation;

use Illuminate\Contracts\Cache\Store as Cache;
use Illuminate\Support\Arr;
use Illuminate\Validation\Factory;
use Illuminate\Validation\ValidationException;
use Symfony\Contracts\Translation\TranslatorInterface;

abstract class AbstractValidator
{
    use ExtensionIdTrait;

    /**
     * @var string
     */
    public static $CORE_VALIDATION_CACHE_KEY = 'core.validation.extension_id_class_names';

    /**
     * @var array
     */
    protected $configuration = [];

    public function addConfiguration($callable)
    {
        $this->configuration[] = $callable;
    }

    /**
     * @var array
     */
    protected $rules = [];

    /**
     * @var Factory
     */
    protected $validator;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @param Factory $validator
     * @param TranslatorInterface $translator
     */
    public function __construct(Factory $validator, TranslatorInterface $translator)
    {
        $this->validator = $validator;
        $this->translator = $translator;
    }

    /**
     * Throw an exception if a model is not valid.
     *
     * @param array $attributes
     */
    public function assertValid(array $attributes)
    {
        $validator = $this->makeValidator($attributes);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }

    /**
     * @return array
     */
    protected function getRules()
    {
        return $this->rules;
    }

    /**
     * @return array
     */
    protected function getMessages()
    {
        return [];
    }

    /**
     * @return array
     */
    protected function getAttributeNames()
    {
        $cache = resolve(Cache::class);

        if ($cache->get(self::$CORE_VALIDATION_CACHE_KEY) !== null) {
            return $cache->get(self::$CORE_VALIDATION_CACHE_KEY);
        }

        $extId = $this->getClassExtensionId();
        $attributeNames = [];

        foreach (array_keys($this->rules) as $attribute) {
            $key = $extId ? "$extId.validation.attributes.$attribute" : "validation.attributes.$attribute";
            $attributeNames[$attribute] = $this->translator->trans($key);
        }

        $cache->forever(self::$CORE_VALIDATION_CACHE_KEY, $attributeNames);

        return $attributeNames;
    }

    /**
     * Make a new validator instance for this model.
     *
     * @param array $attributes
     * @return \Illuminate\Validation\Validator
     */
    protected function makeValidator(array $attributes)
    {
        $rules = Arr::only($this->getRules(), array_keys($attributes));

        $validator = $this->validator->make($attributes, $rules, $this->getMessages());
        $validator->setAttributeNames($this->getAttributeNames());

        foreach ($this->configuration as $callable) {
            $callable($this, $validator);
        }

        return $validator;
    }
}
