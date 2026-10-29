<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Api\Describe;

use Illuminate\Contracts\Validation\ImplicitRule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rules\RequiredIf;

/**
 * Parses Laravel-style validation rules into descriptor constraint hints.
 *
 * Core's `Str::email()` / `regex()` helpers emit rules rather than type schema,
 * so this parser is the only source for those constraints. Rule objects and
 * closures are ignored, except by `requirements()`.
 *
 * @internal Not part of the extension API; use the extenders.
 */
class RuleHints
{
    /**
     * PCRE modifiers that mean the same thing as a JS `RegExp` flag of the same letter.
     */
    protected const array JS_FLAGS = ['i', 'm', 's', 'u'];

    /**
     * Paired delimiters PCRE accepts, opening => closing.
     */
    protected const array BRACKETS = ['(' => ')', '{' => '}', '[' => ']', '<' => '>'];

    /**
     * Laravel's conditional required rules, by the descriptor condition they map to.
     */
    protected const array REQUIRED_CONDITIONS = [
        'required_with' => 'with',
        'required_with_all' => 'withAll',
        'required_without' => 'without',
        'required_without_all' => 'withoutAll',
        'required_if' => 'if',
        'required_unless' => 'unless',
        'required_if_accepted' => 'ifAccepted',
        'required_if_declined' => 'ifDeclined',
    ];

    /**
     * Conditions on the value of one other field: only the first argument names a field.
     */
    protected const array VALUE_CONDITIONS = ['if', 'unless', 'ifAccepted', 'ifDeclined'];

    /**
     * Conditions whose remaining arguments are the values compared against.
     */
    protected const array VALUES_CONDITIONS = ['if', 'unless'];

    /**
     * Rules that are not about presence although their name starts with `required_`.
     */
    protected const array NOT_REQUIRED_RULES = ['required_array_keys'];

    /**
     * Rules that drop the field from validated input, so the client cannot
     * predict whether a required rule next to them applies.
     */
    protected const string EXCLUDE_PREFIX = 'exclude';

    /**
     * `min:` and `max:` depend on the field kind: a length for strings, a value
     * for integers and numbers, an item count for arrays. Other kinds get neither.
     *
     * @param iterable<mixed>|string $rules
     * @param string $kind the descriptor field kind
     * @return array{required?: true, nullable?: true, filled?: true, present?: true, format?: string, minLength?: int, maxLength?: int, minimum?: int|float, maximum?: int|float, minItems?: int, maxItems?: int, enum?: list<string>, pattern?: string, patternFlags?: string}
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

                case 'nullable':
                case 'filled':
                case 'present':
                    $hints[$name] = true;
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
     * Describes how the given rules require a field, for one write mode.
     *
     * - `always`: the plain `required` rule (or `Rule::requiredIf(true)`);
     * - `when`: Laravel's conditional required rules; the field is required when any holds;
     * - `undescribable`: a required-type rule the client cannot evaluate, such as
     *   `Rule::requiredIf(closure)`, an implicit rule object, or any required rule
     *   next to an `exclude_*` rule. The server decides.
     *
     * Field names are passed through: Flarum validates attributes by field name.
     *
     * @param iterable<mixed>|string $rules evaluated rules for one mode
     * @return array{always: bool, when: list<array{rule: string, fields: list<string>, values?: list<string>}>, undescribable: bool}
     */
    public static function requirements(iterable|string $rules): array
    {
        if (is_string($rules)) {
            $rules = explode('|', $rules);
        }

        $always = false;
        $when = [];
        $undescribable = false;
        $excludes = false;

        foreach ($rules as $rule) {
            if ($rule instanceof RequiredIf) {
                if (is_bool($rule->condition)) {
                    $always = $always || $rule->condition;
                } else {
                    $undescribable = true;
                }

                continue;
            }

            // Implicit rules run even when the field is missing, so they may require it.
            if ($rule instanceof ImplicitRule || ($rule instanceof ValidationRule && ($rule->implicit ?? false) === true)) {
                $undescribable = true;

                continue;
            }

            if (! is_string($rule)) {
                continue;
            }

            [$name, $arguments] = array_pad(explode(':', $rule, 2), 2, '');

            if ($name === 'required') {
                $always = true;

                continue;
            }

            if (str_starts_with($name, self::EXCLUDE_PREFIX)) {
                $excludes = true;

                continue;
            }

            if (! str_starts_with($name, 'required_') || in_array($name, self::NOT_REQUIRED_RULES, true)) {
                continue;
            }

            $condition = self::REQUIRED_CONDITIONS[$name] ?? null;
            $parameters = $arguments === '' ? [] : array_map('strval', str_getcsv($arguments, ',', '"', ''));

            $minimum = in_array($condition, self::VALUES_CONDITIONS, true) ? 2 : 1;

            if ($condition === null || count($parameters) < $minimum) {
                $undescribable = true;

                continue;
            }

            if (in_array($condition, self::VALUE_CONDITIONS, true)) {
                $entry = ['rule' => $condition, 'fields' => [$parameters[0]]];

                if (in_array($condition, self::VALUES_CONDITIONS, true)) {
                    $entry['values'] = array_slice($parameters, 1);
                }
            } else {
                $entry = ['rule' => $condition, 'fields' => $parameters];
            }

            $when[] = $entry;
        }

        if ($excludes && ($always || $when)) {
            return ['always' => false, 'when' => [], 'undescribable' => true];
        }

        // An unconditional requirement makes the conditions moot.
        return ['always' => $always, 'when' => $always ? [] : $when, 'undescribable' => $undescribable];
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
