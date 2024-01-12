<?php

namespace Flarum\ExtensionManager;

/**
 * @todo: fix in 2.0
 */
trait AllValidatorRules
{
    protected function makeValidator(array $attributes)
    {
        $rules = $this->getRules();

        $validator = $this->validator->make($attributes, $rules, $this->getMessages());

        foreach ($this->configuration as $callable) {
            $callable($this, $validator);
        }

        return $validator;
    }
}
