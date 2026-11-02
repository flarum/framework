<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Api\Describe;

use BackedEnum;
use Illuminate\Contracts\Validation\ImplicitRule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\ConditionalRules;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\Rules\ExcludeIf;
use Illuminate\Validation\Rules\ExcludeUnless;
use Illuminate\Validation\Rules\In;
use Illuminate\Validation\Rules\NotIn;
use Illuminate\Validation\Rules\ProhibitedIf;
use Illuminate\Validation\Rules\ProhibitedUnless;
use Illuminate\Validation\Rules\RequiredIf;
use Illuminate\Validation\Rules\RequiredUnless;
use Stringable;
use Throwable;

/**
 * Parses Laravel-style validation rules into descriptor constraint hints.
 *
 * Core's `Str::email()` / `regex()` helpers emit rules rather than type schema,
 * so this parser is the only source for those constraints. Stringable rule
 * objects (`Rule::in()`, `Rule::enum()`, ...) are read through their string
 * form; other rule objects and closures are ignored, except by `requirements()`.
 *
 * @internal Not part of the extension API; use the extenders.
 */
class RuleHints
{
    /**
     * Stands in for a rule only the server may check: it is never described,
     * and it makes the field live-validated. See {@see self::isValueList()}.
     */
    public const string SERVER_ONLY = 'flarum_server_only';

    /**
     * PCRE modifiers that mean the same thing as a JS `RegExp` flag of the same letter.
     */
    protected const array JS_FLAGS = ['i', 'm', 's', 'u'];

    protected const array BRACKETS = ['(' => ')', '{' => '}', '[' => ']', '<' => '>'];

    /**
     * Escapes a JS `RegExp` in unicode mode (the client always compiles with `u`)
     * reads the same way as PCRE. Everything else is either PCRE-only (`\Z`, `\h`,
     * `\R`, `\v`, `\N`, `\e`, `\a`, ...) or an identity escape that `u` mode rejects.
     * `\p`, `\x`, `\c`, `\k`, digits, `\b`/`\B` and `-` are checked separately.
     */
    protected const string JS_ESCAPES = 'dDwWsSfnrt^$\\.*+?()[]{}|/';

    /**
     * Group openers after `(?` that JS reads the same way.
     */
    protected const array JS_GROUPS = [':', '=', '!', '<=', '<!'];

    /**
     * Laravel rule => descriptor format.
     */
    protected const array FORMATS = [
        'email' => 'email',
        'url' => 'uri',
        'uuid' => 'uuid',
        'ulid' => 'ulid',
        'ip' => 'ip',
        'ipv4' => 'ipv4',
        'ipv6' => 'ipv6',
        'hex_color' => 'hex-color',
    ];

    /**
     * Rules under which Laravel compares `min`/`max`/`size`/... against the value rather than a length.
     */
    protected const array NUMERIC_RULES = ['numeric', 'integer', 'decimal'];

    /**
     * Laravel rule => descriptor condition.
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
     * Rules the descriptor fully expresses (or that only describe the type), so
     * the client can evaluate them without asking the server.
     */
    protected const array CLIENT_RULES = [
        'required', 'nullable', 'filled', 'present', 'accepted', 'declined', 'sometimes', 'bail',
        'string', 'integer', 'numeric', 'boolean', 'array',
        'distinct', 'multiple_of', 'min', 'gte', 'max', 'lte', 'gt', 'lt', 'size', 'between',
        'digits', 'digits_between', 'in', 'regex',
    ];

    /**
     * @see \Flarum\Api\Endpoint\Concerns\SavesAndValidatesData::assertDataValid()
     */
    protected const array FIELD_PREFIXES = ['attributes.', 'relationships.'];

    /**
     * Size rules (`min`, `max`, `size`, `between`, `gt`, ...) depend on the field
     * kind, as in Laravel: an item count for arrays, a value when the field is
     * numeric (by kind or by a `numeric`/`integer` rule), a length for strings.
     * Other kinds get none. `gt`/`lt` against another field are not described.
     *
     * @param iterable<mixed>|string $rules
     * @param string $kind the descriptor field kind
     * @return array{required?: true, nullable?: true, filled?: true, present?: true, accepted?: true, declined?: true, format?: string, minLength?: int, maxLength?: int, minimum?: int|float, maximum?: int|float, exclusiveMinimum?: int|float, exclusiveMaximum?: int|float, multipleOf?: int|float, minItems?: int, maxItems?: int, uniqueItems?: true, items?: array{enum: list<string>}, enum?: list<string>, pattern?: string, patternFlags?: string, patterns?: list<array{pattern: string, flags?: string}>}
     */
    public static function parse(iterable|string $rules, string $kind = 'string'): array
    {
        $rules = array_filter(static::normalize($rules), 'is_string');
        $names = array_map(fn (string $rule) => explode(':', $rule, 2)[0], $rules);

        $space = match (true) {
            $kind === 'array' => 'items',
            in_array($kind, ['integer', 'number'], true) || array_intersect(self::NUMERIC_RULES, $names) => 'value',
            $kind === 'string' => 'length',
            default => null,
        };

        $hints = [];
        $patterns = [];

        foreach ($rules as $rule) {
            [$name, $arguments] = array_pad(explode(':', $rule, 2), 2, null);
            $parameters = $arguments === null || $arguments === '' ? [] : str_getcsv($arguments, ',', '"', '');

            switch ($name) {
                case 'required':
                    $hints['required'] = true;
                    break;

                case 'nullable':
                case 'filled':
                case 'present':
                case 'accepted':
                case 'declined':
                    $hints[$name] = true;
                    break;

                case 'distinct':
                    if ($kind === 'array') {
                        $hints['uniqueItems'] = true;
                    }
                    break;

                case 'multiple_of':
                    if (isset($parameters[0]) && is_numeric($parameters[0])) {
                        $hints['multipleOf'] = 0 + $parameters[0];
                    }
                    break;

                case 'min':
                case 'gte':
                case 'max':
                case 'lte':
                case 'gt':
                case 'lt':
                    if (isset($parameters[0]) && is_numeric($parameters[0])) {
                        static::bound($hints, $space, $name, 0 + $parameters[0]);
                    }
                    break;

                case 'size':
                    if (isset($parameters[0]) && is_numeric($parameters[0])) {
                        static::bound($hints, $space, 'min', 0 + $parameters[0]);
                        static::bound($hints, $space, 'max', 0 + $parameters[0]);
                    }
                    break;

                case 'between':
                    if (count($parameters) === 2 && is_numeric($parameters[0]) && is_numeric($parameters[1])) {
                        static::bound($hints, $space, 'min', 0 + $parameters[0]);
                        static::bound($hints, $space, 'max', 0 + $parameters[1]);
                    }
                    break;

                case 'digits':
                    if (isset($parameters[0]) && ctype_digit($parameters[0])) {
                        $patterns[] = ['pattern' => "^[0-9]{{$parameters[0]}}$"];
                    }
                    break;

                case 'digits_between':
                    if (count($parameters) === 2 && ctype_digit($parameters[0]) && ctype_digit($parameters[1])) {
                        $patterns[] = ['pattern' => "^[0-9]{{$parameters[0]},{$parameters[1]}}$"];
                    }
                    break;

                case 'in':
                    if ($parameters) {
                        // Laravel checks each item of an array value against the list.
                        if ($kind === 'array') {
                            $hints['items'] = ['enum' => array_map('strval', $parameters)];
                        } else {
                            $hints['enum'] = array_map('strval', $parameters);
                        }
                    }
                    break;


                case 'regex':
                    $pattern = static::pattern((string) $arguments);

                    if ($pattern !== null) {
                        $patterns[] = $pattern[1] === ''
                            ? ['pattern' => $pattern[0]]
                            : ['pattern' => $pattern[0], 'flags' => $pattern[1]];
                    }
                    break;

                default:
                    if (isset(self::FORMATS[$name])) {
                        $hints['format'] = self::FORMATS[$name];
                    }
            }
        }

        // `sometimes` only validates a present field, so `required` only forbids an empty value.
        if (isset($hints['required']) && in_array('sometimes', $names, true)) {
            unset($hints['required']);
            $hints['filled'] = true;
        }

        return array_merge($hints, static::patternHints($patterns));
    }

    /**
     * Folds a list of patterns into `pattern` (+ `patternFlags`) when there is
     * one, or `patterns` when there are several; all must match.
     *
     * @param list<array{pattern: string, flags?: string}> $patterns
     * @return array{pattern?: string, patternFlags?: string, patterns?: list<array{pattern: string, flags?: string}>}
     */
    public static function patternHints(array $patterns): array
    {
        if (count($patterns) > 1) {
            return ['patterns' => $patterns];
        }

        if (! $patterns) {
            return [];
        }

        $hints = ['pattern' => $patterns[0]['pattern']];

        if (($patterns[0]['flags'] ?? '') !== '') {
            $hints['patternFlags'] = $patterns[0]['flags'];
        }

        return $hints;
    }

    /**
     * Describes how the given rules require a field, for one write mode.
     *
     * - `always`: the plain `required` rule (or `Rule::requiredIf(true)`);
     * - `when`: Laravel's conditional required rules; the field is required when any holds;
     * - `undescribable`: a required-type rule the client cannot evaluate, such as
     *   `Rule::requiredIf(closure)`, `Rule::when()`, an implicit rule object, or
     *   any required rule next to an `exclude_*` rule. The server decides.
     *
     * Next to `sometimes`, nothing is required: the rules only run when the field
     * is sent (`parse()` reports `required` as `filled` then).
     *
     * Field names are passed through, without core's `attributes.` / `relationships.`
     * prefix. A `null` compared value matches an absent or null field, as in Laravel.
     *
     * @param iterable<mixed>|string $rules evaluated rules for one mode
     * @return array{always: bool, when: list<array{rule: string, fields: list<string>, values?: list<string|null>}>, undescribable: bool}
     */
    public static function requirements(iterable|string $rules): array
    {
        $always = false;
        $when = [];
        $undescribable = false;
        $excludes = false;
        $sometimes = false;

        foreach (static::normalize($rules) as $rule) {
            if ($rule instanceof RequiredIf || $rule instanceof RequiredUnless || $rule instanceof ConditionalRules) {
                $undescribable = true;

                continue;
            }

            if ($rule instanceof ExcludeIf || $rule instanceof ExcludeUnless) {
                $excludes = true;

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

            if ($name === 'sometimes') {
                $sometimes = true;

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
                $entry = ['rule' => $condition, 'fields' => [static::fieldName($parameters[0])]];

                if (in_array($condition, self::VALUES_CONDITIONS, true)) {
                    $entry['values'] = array_map(fn (string $value) => $value === 'null' ? null : $value, array_slice($parameters, 1));
                }
            } else {
                $entry = ['rule' => $condition, 'fields' => array_map(static::fieldName(...), $parameters)];
            }

            $when[] = $entry;
        }

        if ($sometimes) {
            return ['always' => false, 'when' => [], 'undescribable' => false];
        }

        if ($excludes && ($always || $when)) {
            return ['always' => false, 'when' => [], 'undescribable' => true];
        }

        // An unconditional requirement makes the conditions moot.
        return ['always' => $always, 'when' => $always ? [] : $when, 'undescribable' => $undescribable];
    }

    /**
     * @param iterable<mixed>|string $rules
     * @return list<string>
     */
    public static function ruleNames(iterable|string $rules): array
    {
        return array_values(array_map(
            fn (string $rule) => explode(':', $rule, 2)[0],
            array_filter(static::normalize($rules), 'is_string')
        ));
    }

    /**
     * The parameters of each string rule, by rule name (the first occurrence wins).
     *
     * @param iterable<mixed>|string $rules
     * @return array<string, list<string>>
     */
    public static function ruleParameters(iterable|string $rules): array
    {
        $parameters = [];

        foreach (array_filter(static::normalize($rules), 'is_string') as $rule) {
            [$name, $arguments] = array_pad(explode(':', $rule, 2), 2, '');

            $parameters[$name] ??= $arguments === '' ? [] : array_map('strval', str_getcsv($arguments, ',', '"', ''));
        }

        return $parameters;
    }

    /**
     * The descriptor constraint keys a Laravel rule produces, so a custom message
     * for the rule can be sent along with the constraint it explains. The caller
     * keeps the keys that are present for the field's kind.
     *
     * @return list<string>
     */
    public static function constraintKeys(string $rule): array
    {
        if ($rule === 'required' || (str_starts_with($rule, 'required_') && ! in_array($rule, self::NOT_REQUIRED_RULES, true))) {
            return ['required'];
        }

        $lower = ['minLength', 'minimum', 'minItems'];
        $upper = ['maxLength', 'maximum', 'maxItems'];

        return match ($rule) {
            'regex', 'digits', 'digits_between' => ['pattern'],
            'min', 'gte' => $lower,
            'max', 'lte' => $upper,
            'gt' => ['minLength', 'exclusiveMinimum', 'minItems'],
            'lt' => ['maxLength', 'exclusiveMaximum', 'maxItems'],
            'size', 'between' => [...$lower, ...$upper],
            'in', 'enum' => ['enum', 'items'],
            'multiple_of' => ['multipleOf'],
            'distinct' => ['uniqueItems'],
            'accepted', 'declined', 'filled' => [$rule],
            default => isset(self::FORMATS[$rule]) ? ['format'] : [],
        };
    }

    /**
     * Whether a rule is a list of allowed or forbidden values (`in`, `not_in`,
     * `enum`). A closure building such a list may read data the actor cannot see
     * (`Rule::in(Group::pluck(...))`), so the describer replaces it with
     * {@see self::SERVER_ONLY} rather than publishing the list.
     */
    public static function isValueList(mixed $rule): bool
    {
        if ($rule instanceof In || $rule instanceof NotIn || $rule instanceof Enum) {
            return true;
        }

        if (! is_string($rule)) {
            return false;
        }

        foreach (explode('|', $rule) as $part) {
            if (in_array(strtolower(explode(':', $part, 2)[0]), ['in', 'not_in', 'enum'], true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Whether the rules hold anything the client cannot evaluate from the
     * descriptor: rule objects and closures that have no string form (`Password`,
     * `Rule::requiredIf(fn)`, custom rules), and string rules the descriptor does
     * not express (`unique`, `exists`, `confirmed`, `date`, ...).
     *
     * @param iterable<mixed>|string $rules evaluated rules for one mode
     */
    public static function needsServer(iterable|string $rules): bool
    {
        foreach (static::normalize($rules) as $rule) {
            if (! is_string($rule)) {
                return true;
            }

            $name = explode(':', $rule, 2)[0];

            if (in_array($name, self::CLIENT_RULES, true) || isset(self::FORMATS[$name])) {
                continue;
            }

            if (isset(self::REQUIRED_CONDITIONS[$name])) {
                continue;
            }

            return true;
        }

        return false;
    }

    /**
     * Turns a PCRE pattern body into one a JS `RegExp` (in unicode mode) reads the
     * same way: `\A` and `\z` become `^` and `$`. Returns null when the pattern
     * uses a construct JS has no equivalent for (`\Z`, `\h`, `\R`, possessive
     * quantifiers, atomic groups, inline modifiers, POSIX classes, ...).
     *
     * PCRE's `$` also matches before a trailing newline, JS's does not: JS is
     * stricter only for such input, which the server then rejects anyway.
     *
     * @param string $flags the PCRE modifiers
     */
    public static function jsPattern(string $pattern, string $flags = ''): ?string
    {
        $out = '';
        $length = strlen($pattern);
        $inClass = false;
        $quantified = false;
        $multiline = str_contains($flags, 'm');
        $unicode = str_contains($flags, 'u');

        for ($i = 0; $i < $length; $i++) {
            $char = $pattern[$i];

            if ($char === '\\') {
                $next = $pattern[$i + 1] ?? '';
                $i++;
                $quantified = false;

                if ($next === 'A' || $next === 'z') {
                    // Under `m`, JS's `^`/`$` match at every line; PCRE's `\A`/`\z` do not.
                    if ($inClass || $multiline) {
                        return null;
                    }

                    $out .= $next === 'A' ? '^' : '$';

                    continue;
                }

                $escape = static::jsEscape($pattern, $i, $inClass, $unicode);

                if ($escape === null) {
                    return null;
                }

                $out .= '\\'.$escape;
                $i += strlen($escape) - 1;

                continue;
            }

            if ($inClass) {
                if ($char === '[' && ($pattern[$i + 1] ?? '') === ':') {
                    return null;
                }

                if ($char === ']') {
                    $inClass = false;
                }

                $out .= $char;

                continue;
            }

            if ($char === '[') {
                $inClass = true;
                $out .= $char;

                // A leading `]` (or `^]`) is a literal.
                foreach (['^', ']'] as $literal) {
                    if (($pattern[$i + 1] ?? '') === $literal) {
                        $out .= $literal;
                        $i++;
                    }
                }

                $quantified = false;

                continue;
            }

            if ($char === '+' && $quantified) {
                return null;
            }

            if ($char === '(' && ($pattern[$i + 1] ?? '') === '?') {
                $rest = substr($pattern, $i + 2);
                $named = (bool) preg_match('/^<[A-Za-z_]/', $rest);
                $supported = array_filter(self::JS_GROUPS, fn (string $opener) => str_starts_with($rest, $opener));

                if (! $named && ! $supported) {
                    return null;
                }
            }

            $quantified = in_array($char, ['*', '+', '?', '}'], true)
                && ! ($char === '?' && $i > 0 && $pattern[$i - 1] === '(');
            $out .= $char;
        }

        return $out;
    }

    /**
     * The escape sequence starting at `$i` (just after the backslash), when JS's
     * unicode mode reads it as PCRE does; null otherwise.
     */
    protected static function jsEscape(string $pattern, int $i, bool $inClass, bool $unicode): ?string
    {
        $next = $pattern[$i] ?? '';
        $rest = substr($pattern, $i);

        if ($next === '') {
            return null;
        }

        if (str_contains(self::JS_ESCAPES, $next)) {
            return $next;
        }

        return match (true) {
            $next === '-' => $inClass ? $next : null,
            $next === 'b' => $next,
            $next === 'B' => $inClass ? null : $next,
            // Without `u`, PCRE matches `\p{..}` against bytes; JS (always `u`) against code points.
            $next === 'p' || $next === 'P' => $unicode && preg_match('/^[pP]\{\^?[A-Za-z_]+(=[A-Za-z_]+)?\}/', $rest, $m) ? $m[0] : null,
            $next === 'x' => preg_match('/^x[0-9A-Fa-f]{2}/', $rest, $m) ? $m[0] : null,
            $next === 'c' => preg_match('/^c[A-Za-z]/', $rest, $m) ? $m[0] : null,
            $next === 'k' => ! $inClass && preg_match('/^k<[A-Za-z_][A-Za-z0-9_]*>/', $rest, $m) ? $m[0] : null,
            // `\0` is NUL in both, unless an octal escape follows; `\1`.. are backreferences outside a class.
            $next === '0' => ctype_digit($pattern[$i + 1] ?? '') ? null : $next,
            ctype_digit($next) => $inClass || ctype_digit($pattern[$i + 1] ?? '') ? null : $next,
            default => null,
        };
    }

    /**
     * Reads rule objects through their string form where that is safe.
     *
     * Objects whose string form runs a closure (`Rule::requiredIf(fn)`,
     * `Rule::excludeIf(fn)`, ...) are kept for `requirements()`, as is
     * `Rule::when()`. `Rule::enum()` is only read for backed enums, the only
     * ones a submitted value can match.
     *
     * @param iterable<mixed>|string $rules
     * @return list<mixed>
     */
    protected static function normalize(iterable|string $rules): array
    {
        if (is_string($rules)) {
            $rules = explode('|', $rules);
        }

        $normalized = [];

        foreach ($rules as $rule) {
            if ($rule instanceof Enum) {
                $type = (fn () => $this->type)->call($rule);

                if (! is_string($type) || ! is_subclass_of($type, BackedEnum::class)) {
                    continue;
                }
            } elseif (static::isConditionRule($rule) && ! is_bool($rule->condition)) {
                $normalized[] = $rule;

                continue;
            }

            if ($rule instanceof Stringable && ! $rule instanceof ConditionalRules) {
                try {
                    $rule = (string) $rule;
                } catch (Throwable) {
                    continue;
                }

                // `Rule::requiredIf(false)` and friends stringify to nothing.
                if ($rule === '') {
                    continue;
                }
            }

            $normalized[] = $rule;
        }

        return $normalized;
    }

    /**
     * Laravel's rule objects whose string form depends on a public `$condition`
     * (a closure or a bool). Matched by class: a custom rule's `$condition` may
     * be protected or uninitialized.
     *
     * @phpstan-assert-if-true RequiredIf|RequiredUnless|ExcludeIf|ExcludeUnless|ProhibitedIf|ProhibitedUnless $rule
     */
    protected static function isConditionRule(mixed $rule): bool
    {
        return $rule instanceof RequiredIf || $rule instanceof RequiredUnless
            || $rule instanceof ExcludeIf || $rule instanceof ExcludeUnless
            || $rule instanceof ProhibitedIf || $rule instanceof ProhibitedUnless;
    }

    /**
     * Sets a lower or upper size bound in the given size space.
     *
     * @param 'value'|'length'|'items'|null $space
     * @param 'min'|'gte'|'gt'|'max'|'lte'|'lt' $rule
     */
    protected static function bound(array &$hints, ?string $space, string $rule, int|float $value): void
    {
        $lower = in_array($rule, ['min', 'gte', 'gt'], true);

        if ($space === 'value') {
            $key = match ($rule) {
                'min', 'gte' => 'minimum',
                'max', 'lte' => 'maximum',
                'gt' => 'exclusiveMinimum',
                'lt' => 'exclusiveMaximum',
            };

            $hints[$key] = $value;

            return;
        }

        if ($space === null) {
            return;
        }

        // Lengths and counts are whole numbers: `gt:2` is at least 3.
        $count = match ($rule) {
            'min', 'gte' => (int) ceil($value),
            'max', 'lte' => (int) floor($value),
            'gt' => (int) floor($value) + 1,
            'lt' => (int) ceil($value) - 1,
        };

        $hints[($space === 'items' ? ($lower ? 'minItems' : 'maxItems') : ($lower ? 'minLength' : 'maxLength'))] = $count;
    }

    public static function fieldName(string $field): string
    {
        foreach (self::FIELD_PREFIXES as $prefix) {
            if (str_starts_with($field, $prefix)) {
                return substr($field, strlen($prefix));
            }
        }

        return $field;
    }

    /**
     * Splits a PHP regex into a JSON-schema style pattern and JS flags.
     *
     * Any delimiter PCRE accepts works, including paired brackets. Returns null
     * when the regex is malformed or uses a modifier or construct JS has no
     * equivalent for, so the client never validates against a pattern with a
     * different meaning.
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
        $pattern = static::jsPattern(substr($regex, 1, $end - 1), $modifiers);

        return $pattern === null ? null : [$pattern, $flags];
    }
}
