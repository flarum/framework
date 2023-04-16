<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Filter;

use Flarum\Foundation\ValidationException as FlarumValidationException;
use Flarum\Locale\Translator;

trait ValidateFilterTrait
{
    /**
     * @throws FlarumValidationException
     */
    protected function asArray($filterValue, $multidimensional = false): array
    {
        if (is_array($filterValue)) {
            $value = array_map(function ($subValue) use ($multidimensional) {
                if (is_array($subValue) && ! $multidimensional) {
                    $this->throwValidationException('core.api.invalid_filter_type.must_not_be_multidimensional_array_message');
                }

                return trim($subValue, '"');
            }, $filterValue);
        } else {
            $value = explode(',', trim($filterValue, '"'));
        }

        return $value;
    }

    /**
     * @throws FlarumValidationException
     */
    protected function asString($filterValue): string
    {
        if (is_array($filterValue)) {
            $this->throwValidationException('core.api.invalid_filter_type.must_not_be_array_message');
        }

        return trim($filterValue, '"');
    }

    /**
     * @throws FlarumValidationException
     */
    protected function asInt($filterValue): int
    {
        if (! is_numeric($filterValue)) {
            $this->throwValidationException('core.api.invalid_filter_type.must_be_numeric_message');
        }

        return (int) $this->asString($filterValue);
    }

    /**
     * @throws FlarumValidationException
     */
    protected function asIntArray($filterValue): array
    {
        return array_map(function ($value) {
            return $this->asInt($value);
        }, $this->asArray($filterValue));
    }

    /**
     * @throws FlarumValidationException
     */
    protected function asBool($filterValue): bool
    {
        return $this->asString($filterValue) === '1';
    }

    /**
     * @throws FlarumValidationException
     */
    private function throwValidationException(string $messageCode): void
    {
        $translator = resolve(Translator::class);

        throw new FlarumValidationException([
            'message' => $translator->trans($messageCode, ['{filter}' => $this->getFilterKey()]),
        ]);
    }
}
