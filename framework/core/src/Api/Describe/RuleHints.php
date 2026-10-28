<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Api\Describe;

/**
 * Parses Laravel-style validation rules into descriptor constraint hints.
 *
 * Core's `Str::email()` / `regex()` helpers emit rules rather than type schema,
 * so this parser is the only source for those constraints. Rule objects and
 * closures are ignored.
 *
 * @experimental May change in 2.1 without a BC break.
 */
class RuleHints
{
    /**
     * PCRE modifiers that mean the same thing as a JS `RegExp` flag of the same letter.
     */
    protected const JS_FLAGS = ['i', 'm', 's', 'u'];

    /**
     * Paired delimiters PCRE accepts, opening => closing.
     */
    protected const BRACKETS = ['(' => ')', '{' => '}', '[' => ']', '<' => '>'];

    /**
     * `min:` and `max:` depend on the field kind: a length for strings, a value
     * for integers and numbers, an item count for arrays. Other kinds get neither.
     *
     * @param iterable<mixed>|string $rules
     * @param string $kind the descriptor field kind
     * @return array{required?: true, format?: string, minLength?: int, maxLength?: int, minimum?: int|float, maximum?: int|float, minItems?: int, maxItems?: int, enum?: list<string>, pattern?: string, patternFlags?: string}
     */
    public static function parse(iterable|string $rules, string $kind = 'string'): array
    {
        if (is_string($rules)) {
            $rules = explode('|', $rules);
        }

        [$minKey, $maxKey] = match ($kind) {
            'string' => ['minLength', 'maxLength'],
            'integer', 'number' => ['minimum', 'maximum'],
            'array' => ['minItems', 'maxItems'],
            default => [null, null],
        };

        $hints = [];

        foreach ($rules as $rule) {
            if (! is_string($rule)) {
                continue;
            }

            [$name, $arguments] = array_pad(explode(':', $rule, 2), 2, null);

            switch ($name) {
                case 'required':
                    $hints['required'] = true;
                    break;

                case 'email':
                    $hints['format'] = 'email';
                    break;

                case 'url':
                    $hints['format'] = 'url';
                    break;

                case 'min':
                case 'max':
                    $key = $name === 'min' ? $minKey : $maxKey;

                    if ($key !== null && is_numeric($arguments)) {
                        $hints[$key] = $kind === 'number' ? 0 + $arguments : (int) $arguments;
                    }
                    break;

                case 'in':
                    if ($arguments !== null && $arguments !== '') {
                        // Rule::in() quotes its values, so parse them as CSV.
                        $hints['enum'] = array_map('strval', str_getcsv($arguments, ',', '"', ''));
                    }
                    break;

                case 'regex':
                    unset($hints['pattern'], $hints['patternFlags']);

                    $pattern = static::pattern((string) $arguments);

                    if ($pattern !== null) {
                        $hints['pattern'] = $pattern[0];

                        if ($pattern[1] !== '') {
                            $hints['patternFlags'] = $pattern[1];
                        }
                    }
                    break;
            }
        }

        return $hints;
    }

    /**
     * Splits a PHP regex into a JSON-schema style pattern and JS flags.
     *
     * Any delimiter PCRE accepts works, including paired brackets. Returns null
     * when the regex is malformed or uses a modifier JS has no equivalent for,
     * so the client never validates against a pattern with a different meaning.
     *
     * @return array{string, string}|null [pattern, flags]
     */
    protected static function pattern(string $regex): ?array
    {
        $regex = ltrim($regex);

        if (strlen($regex) < 2) {
            return null;
        }

        $delimiter = $regex[0];

        if (ctype_alnum($delimiter) || $delimiter === '\\') {
            return null;
        }

        $closing = self::BRACKETS[$delimiter] ?? $delimiter;
        $end = strrpos($regex, $closing);

        if ($end === false || $end === 0) {
            return null;
        }

        $modifiers = substr($regex, $end + 1);

        if (array_diff(str_split($modifiers, 1), self::JS_FLAGS, ['']) !== []) {
            return null;
        }

        $flags = implode('', array_intersect(self::JS_FLAGS, str_split($modifiers, 1)));

        return [substr($regex, 1, $end - 1), $flags];
    }
}
