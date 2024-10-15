<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\ExtensionManager;

/**
 * @todo: fix in 2.0
 */
trait AllValidatorRules
{
    protected function makeValidator(array $attributes)
    {
        $rules = $this->getRules();
        $messages = $this->getMessages();
        $customAttributes = $this->attributes();

        $validator = $this->validator->make($attributes, $rules, $messages, $customAttributes);

        foreach ($this->configuration as $callable) {
            $callable($this, $validator);
        }

        return $validator;
    }
}
