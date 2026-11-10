<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Api\Describe;

use Flarum\Api\Context;
use Flarum\Api\Resource\AbstractDatabaseResource;
use Flarum\Foundation\Config;
use Flarum\Locale\TranslatorInterface;
use Illuminate\Support\Str as StrHelper;
use Psr\Log\LoggerInterface;
use Symfony\Component\Translation\TranslatorBagInterface;
use Throwable;
use Tobyz\JsonApiServer\Schema\Field\Attribute;
use Tobyz\JsonApiServer\Schema\Field\Field;
use Tobyz\JsonApiServer\Schema\Field\Relationship;
use Tobyz\JsonApiServer\Schema\Field\ToMany;
use Tobyz\JsonApiServer\Schema\Type\Arr;
use Tobyz\JsonApiServer\Schema\Type\Boolean;
use Tobyz\JsonApiServer\Schema\Type\Date;
use Tobyz\JsonApiServer\Schema\Type\DateTime;
use Tobyz\JsonApiServer\Schema\Type\Integer;
use Tobyz\JsonApiServer\Schema\Type\Number;
use Tobyz\JsonApiServer\Schema\Type\Str;

/**
 * Describes a single field against synthetic contexts.
 *
 * Visibility fails closed: a field is visible only when a read mode says so,
 * and a check that throws counts as "not visible". Writability fails open and
 * is flagged `conditional`. Requirements are described per write
 * mode: `required` when unconditional, `requiredWhen` for Laravel's conditional
 * required rules, and `requiredConditional` when a required-type rule cannot
 * be described, so the server decides. Constraints come from the same
 * evaluated rules: `constraints` for create (or update, when the field cannot
 * be created), and `constraintsOnUpdate` when update's differ.
 *
 * @internal Not part of the extension API; use the extenders.
 *
 * @phpstan-type Condition array{rule: string, fields: list<string>, values?: list<string|null>}
 * @phpstan-type Constraints array{
 *     filled?: true, present?: true, accepted?: true, declined?: true, format?: string,
 *     minLength?: int, maxLength?: int, minimum?: int|float, maximum?: int|float,
 *     exclusiveMinimum?: int|float, exclusiveMaximum?: int|float, multipleOf?: int|float,
 *     minItems?: int, maxItems?: int, uniqueItems?: true, items?: array{enum: list<string>},
 *     enum?: list<string>,
 *     pattern?: string, patternFlags?: string, patterns?: list<array{pattern: string, flags?: string}>,
 *     messages?: array<string, string>
 * }
 * @phpstan-type UiHints array{
 *     column?: bool|int, display?: string, placeholder?: string, widget?: string, help?: string,
 *     hidden?: bool, group?: string, priority?: int, visibleWhen?: list<Condition>,
 *     markRequired?: bool,
 *     ...<string, mixed>
 * }
 * @phpstan-type FieldDescription array{
 *     name: string,
 *     kind: 'string'|'boolean'|'integer'|'number'|'datetime'|'date'|'array'|'toOne'|'toMany',
 *     label: string,
 *     visible?: false,
 *     required?: array{create?: true, update?: true},
 *     requiredWhen?: array{create?: list<Condition>, update?: list<Condition>},
 *     requiredConditional?: true,
 *     writable?: array{create?: true, update?: true},
 *     nullable?: true,
 *     conditional?: true,
 *     relationship?: array{types: list<string>},
 *     constraints?: Constraints,
 *     constraintsOnUpdate?: Constraints,
 *     enumLabels?: array<string, string>,
 *     default?: scalar,
 *     sortable?: true,
 *     ui?: UiHints
 * }
 */
class FieldDescriber
{
    protected const array CONSTRAINT_KEYS = [
        'minLength', 'maxLength', 'pattern', 'patternFlags', 'enum', 'format',
        'minimum', 'maximum', 'exclusiveMinimum', 'exclusiveMaximum', 'multipleOf',
        'minItems', 'maxItems', 'uniqueItems', 'items',
    ];

    /**
     * The `ui` hints the generic admin components read. Others pass through,
     * and are logged in debug mode, as they are most likely a typo.
     */
    public const array UI_KEYS = [
        'column', 'display', 'label', 'placeholder', 'widget', 'help', 'hidden',
        'group', 'priority', 'visibleWhen', 'markRequired',
    ];

    /**
     * Lookups are memoised: a descriptor asks for the same keys once per field and mode.
     *
     * @var array<string, string|null>
     */
    protected array $translations = [];

    public function __construct(
        protected TranslatorInterface $translator,
        protected Config $config,
        protected LoggerInterface $logger
    ) {
    }

    /**
     * @param array{create: DescribeContext, update: DescribeContext, show: DescribeContext, index: DescribeContext} $contexts
     * @param array<string, true> $sortable names of sorts visible to the actor
     * @param list<'create'|'update'> $writeModes the write modes the actor has at type level;
     *                                           create is dropped when the actor cannot create
     * @return FieldDescription|null
     */
    public function describe(Field $field, AbstractDatabaseResource $resource, array $contexts, array $sortable, array $writeModes = ['create', 'update']): ?array
    {
        try {
            return $this->describeField($field, $resource, $contexts, $sortable, $writeModes, true);
        } catch (Throwable $e) {
            // Fail closed on rules that cannot be inspected: the field is still
            // described, but the server decides what it requires and accepts.
            $this->report($e, $field, $resource->type());

            return $this->describeField($field, $resource, $contexts, $sortable, $writeModes, false);
        }
    }

    /**
     * @param array{create: DescribeContext, update: DescribeContext, show: DescribeContext, index: DescribeContext} $contexts
     * @param array<string, true> $sortable
     * @param list<'create'|'update'> $writeModes
     * @return FieldDescription|null
     */
    protected function describeField(Field $field, AbstractDatabaseResource $resource, array $contexts, array $sortable, array $writeModes, bool $inspectRules): ?array
    {
        $visible = $this->visible($field, $contexts);

        $conditional = false;
        $writable = [];
        $required = [];
        $requiredWhen = [];
        $requiredConditional = false;
        $rules = [];

        foreach ($writeModes as $mode) {
            [$isWritable, $failed] = $this->writable($field, $contexts[$mode]);

            $conditional = $conditional || $failed;

            if ($isWritable) {
                $writable[$mode] = true;
                $rules[$mode] = $inspectRules ? $this->rules($field, $contexts[$mode]) : null;

                $requirements = $rules[$mode] === null
                    ? ['always' => false, 'when' => [], 'undescribable' => true]
                    : RuleHints::requirements($rules[$mode]);

                if ($requirements['always']) {
                    $required[$mode] = true;
                }

                if ($requirements['when']) {
                    $requiredWhen[$mode] = $requirements['when'];
                }

                $requiredConditional = $requiredConditional || $requirements['undescribable'];
            }
        }

        // A field that cannot be seen and cannot be written is of no use to a client.
        if (! $visible && ! $writable) {
            return null;
        }

        $kind = $this->kind($field);
        $ui = method_exists($field, 'getUi') ? $field->getUi() : [];

        $description = [
            'name' => $field->name,
            'kind' => $kind,
            'label' => $this->label($field, $resource->type(), $ui['label'] ?? null),
        ];

        if (! $visible) {
            $description['visible'] = false;
        }

        if ($required) {
            $description['required'] = $required;
        }

        if ($requiredWhen) {
            $description['requiredWhen'] = $requiredWhen;
        }

        if ($requiredConditional) {
            $description['requiredConditional'] = true;
        }

        if ($writable) {
            $description['writable'] = $writable;
        }

        if ($field->nullable || $this->hasRule($rules, 'nullable')) {
            $description['nullable'] = true;
        }

        if ($conditional) {
            $description['conditional'] = true;
        }

        if ($field instanceof Relationship) {
            // `$collections` is a typed property that a custom relationship may never set.
            $description['relationship'] = ['types' => isset($field->collections) ? array_values($field->collections) : []];
        } else {
            $requiredModes = array_fill_keys(array_keys($required + $requiredWhen), true);

            $description += $this->constraintsByMode($field, $kind, $rules, $contexts, $requiredModes, $description['label']);

            if ($enumLabels = $this->enumLabels($resource->type(), $field->name, $this->enumValues($description))) {
                $description['enumLabels'] = $enumLabels;
            }

            // A default only fills a create form.
            $default = in_array('create', $writeModes, true)
                ? $this->defaultValue($field, $kind)
                : null;

            if ($default !== null) {
                $description['default'] = $default;
            }
        }

        if (isset($sortable[$field->name])) {
            $description['sortable'] = true;
        }

        $ui = $this->ui($ui, $field, $resource->type());

        if ($ui) {
            $description['ui'] = $ui;
        }

        return $description;
    }

    public function report(Throwable $e, Field $field, string $type): void
    {
        if ($this->config->inDebugMode()) {
            $this->logger->warning(sprintf(
                'Could not fully describe field [%s] of resource [%s]: %s',
                $field->name,
                $type,
                $e->getMessage()
            ), ['exception' => $e]);
        }
    }

    /**
     * Prepares the `ui` hints for the client: the label is resolved separately,
     * `placeholder`, `help` and `group` are translated when they are existing
     * translation keys, and `visibleWhen` is normalized to a list of conditions.
     *
     * @param array<string, mixed> $ui
     * @return array<string, mixed>
     */
    protected function ui(array $ui, Field $field, string $type): array
    {
        unset($ui['label']);

        foreach (['placeholder', 'help', 'group'] as $key) {
            if (isset($ui[$key]) && is_string($ui[$key])) {
                $ui[$key] = $this->translate($ui[$key]) ?? $ui[$key];
            }
        }

        if (array_key_exists('visibleWhen', $ui)) {
            $conditions = static::conditions($ui['visibleWhen']);

            if ($conditions) {
                $ui['visibleWhen'] = $conditions;
            } else {
                unset($ui['visibleWhen']);
            }
        }

        if ($this->config->inDebugMode() && $unknown = array_diff(array_keys($ui), self::UI_KEYS)) {
            $this->logger->warning(sprintf(
                'Unknown ui hint(s) [%s] on field [%s] of resource [%s].',
                implode(', ', $unknown),
                $field->name,
                $type
            ));
        }

        return $ui;
    }

    /**
     * Normalizes `visibleWhen`: a condition (`['rule' => 'if', 'fields' => [...], 'values' => [...]]`),
     * a list of them, or Laravel-style shorthands (`'if:type,developer'`, `'with:email'`,
     * `'required_without:token'`). Conditions that cannot be read are dropped.
     *
     * @return list<array{rule: string, fields: list<string>, values?: list<string|null>}>
     */
    public static function conditions(mixed $value): array
    {
        if (is_string($value) || (is_array($value) && isset($value['rule']))) {
            $value = [$value];
        }

        if (! is_array($value)) {
            return [];
        }

        $conditions = [];

        foreach ($value as $condition) {
            if (is_string($condition)) {
                $rule = str_starts_with($condition, 'required_') ? $condition : 'required_'.StrHelper::snake($condition);
                array_push($conditions, ...RuleHints::requirements([$rule])['when']);
            } elseif (is_array($condition) && is_string($condition['rule'] ?? null) && is_array($condition['fields'] ?? null)) {
                $entry = ['rule' => $condition['rule'], 'fields' => array_values(array_map('strval', $condition['fields']))];

                if (isset($condition['values']) && is_array($condition['values'])) {
                    $entry['values'] = array_values(array_map(fn ($v) => $v === null ? null : (string) $v, $condition['values']));
                }

                $conditions[] = $entry;
            }
        }

        return $conditions;
    }

    /**
     * The enum values the field accepts, in any write mode or by its type.
     *
     * @param array<string, mixed> $description
     * @return list<string>
     */
    protected function enumValues(array $description): array
    {
        $values = [];

        foreach (['constraints', 'constraintsOnUpdate'] as $key) {
            $constraints = $description[$key] ?? [];

            array_push($values, ...($constraints['enum'] ?? []), ...($constraints['items']['enum'] ?? []));
        }

        return array_values(array_unique(array_map('strval', $values)));
    }

    /**
     * Translated labels of enum values, by convention: `{prefix}.{value}`, then
     * `{prefix}_enum.{value}` (for when `{prefix}` itself is a label), where the
     * prefix is `core.admin.resource.{type}.{snake_name}`. Values without a
     * translation are left out: the client shows the raw value.
     *
     * @param list<string> $values
     * @return array<string, string>
     */
    public function enumLabels(string $type, string $name, array $values, string $infix = ''): array
    {
        $prefix = "core.admin.resource.$type.$infix".StrHelper::snake($name);
        $labels = [];

        foreach ($values as $value) {
            $value = (string) $value;
            $label = $this->translate("$prefix.$value") ?? $this->translate("{$prefix}_enum.$value");

            if ($label !== null) {
                $labels[$value] = $label;
            }
        }

        return $labels;
    }

    /**
     * A field is visible when it would be serialized on at least one read endpoint.
     *
     * @param array<string, DescribeContext> $contexts
     */
    protected function visible(Field $field, array $contexts): bool
    {
        foreach (['show', 'index'] as $mode) {
            try {
                if ($field->isVisible($contexts[$mode]->withField($field))) {
                    return true;
                }
            } catch (Throwable) {
                // Fail closed.
            }
        }

        return false;
    }

    /**
     * @return array{bool, bool} [writable, conditional]; a check that throws fails open, flagged conditional
     */
    protected function writable(Field $field, Context $context): array
    {
        if (! $field->writable) {
            return [false, false];
        }

        try {
            return [(bool) $field->isWritable($context->withField($field)), false];
        } catch (Throwable) {
            return [true, true];
        }
    }

    /**
     * The field's rules evaluated for one write mode, so conditional rules only
     * count where they apply. Null when they cannot be evaluated: the server decides.
     *
     * @return list<mixed>|null
     */
    protected function rules(Field $field, Context $context): ?array
    {
        if (! method_exists($field, 'getEvaluatedRules')) {
            return [];
        }

        try {
            // A value list built by a closure may hold data the actor cannot see: never published.
            return array_map(
                fn (array $entry) => $entry['computed'] && RuleHints::isValueList($entry['rule']) ? RuleHints::SERVER_ONLY : $entry['rule'],
                $field->getEvaluatedRules($context->withField($field))
            );
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * Whether the given string rule applies in any write mode.
     *
     * @param array<string, list<mixed>|null> $rules evaluated rules by write mode
     */
    protected function hasRule(array $rules, string $name): bool
    {
        foreach ($rules as $modeRules) {
            if (in_array($name, $modeRules ?? [], true)) {
                return true;
            }
        }

        return false;
    }

    protected function kind(Field $field): string
    {
        if ($field instanceof Relationship) {
            return $field instanceof ToMany ? 'toMany' : 'toOne';
        }

        $type = $field instanceof Attribute ? $field->type : null;

        return match (true) {
            $type instanceof Boolean => 'boolean',
            $type instanceof Integer => 'integer',
            $type instanceof Number => 'number',
            $type instanceof DateTime => 'datetime',
            $type instanceof Date => 'date',
            $type instanceof Arr => 'array',
            $type instanceof Str => 'string',
            default => 'string',
        };
    }

    /**
     * Resolves the label:
     * 1. `ui.label`, translated when it is an existing key, otherwise literal;
     * 2. `core.admin.resource.{type}.{snake_name}`, when it exists;
     * 3. the humanised field name (`joinTime` becomes "Join time").
     */
    protected function label(Field $field, string $type, mixed $uiLabel): string
    {
        if (is_string($uiLabel) && $uiLabel !== '') {
            return $this->translate($uiLabel) ?? $uiLabel;
        }

        $snake = StrHelper::snake($field->name);

        return $this->translate("core.admin.resource.$type.$snake")
            ?? ucfirst(str_replace('_', ' ', $snake));
    }

    public function translate(string $key): ?string
    {
        // Only key-shaped strings: a literal label must not reach the message formatter.
        if (! preg_match('/^[a-z0-9_-]+(\.[a-z0-9_-]+)+$/i', $key)) {
            return null;
        }

        if (array_key_exists($key, $this->translations)) {
            return $this->translations[$key];
        }

        $exists = $this->translator instanceof TranslatorBagInterface
            ? $this->translator->getCatalogue()->has($key)
            : $this->translator->trans($key) !== $key;

        return $this->translations[$key] = $exists ? $this->translator->trans($key) : null;
    }

    /**
     * The `constraints` and `constraintsOnUpdate` keys. A field that is not
     * writable for the actor only reports its type's constraints, so rules that
     * would not apply to the actor are never described.
     *
     * Each mode's constraints carry `messages`: the field's custom validation
     * messages for that mode, keyed by the constraint they explain.
     *
     * @param array<string, list<mixed>|null> $rules evaluated rules by write mode
     * @param array<string, DescribeContext> $contexts
     * @param array<string, true> $requiredModes write modes in which the field is (conditionally) required
     * @return array{constraints?: array<string, mixed>, constraintsOnUpdate?: array<string, mixed>}
     */
    protected function constraintsByMode(Field $field, string $kind, array $rules, array $contexts = [], array $requiredModes = [], string $label = ''): array
    {
        $type = $this->typeConstraints($field);

        if (! $rules) {
            return $type ? ['constraints' => $type] : [];
        }

        $byMode = [];

        foreach ($rules as $mode => $modeRules) {
            $constraints = $this->constraints($type, $modeRules ?? [], $kind);

            if (isset($contexts[$mode]) && $messages = $this->messages($field, $contexts[$mode], $modeRules ?? [], $constraints, isset($requiredModes[$mode]), $label)) {
                $constraints['messages'] = $messages;
            }

            $byMode[$mode] = $constraints;
        }
        $primary = $byMode['create'] ?? $byMode['update'];

        $description = $primary ? ['constraints' => $primary] : [];

        if (isset($byMode['create'], $byMode['update']) && $byMode['update'] != $byMode['create']) {
            // A full replacement, so a client can swap sets by mode.
            $description['constraintsOnUpdate'] = $byMode['update'];
        }

        return $description;
    }

    /**
     * The json-api-server type schema, as descriptor constraints: unset and
     * default values dropped, OpenAPI 3.0 boolean exclusive bounds turned into
     * JSON Schema 2020 numbers, and only an item enum kept from `items`.
     *
     * @return array<string, mixed>
     */
    protected function typeConstraints(Field $field): array
    {
        if (! $field instanceof Attribute || ! $field->type) {
            return [];
        }

        $schema = $field->type->schema();
        $constraints = array_intersect_key($schema, array_flip(self::CONSTRAINT_KEYS));

        foreach (['minimum' => 'exclusiveMinimum', 'maximum' => 'exclusiveMaximum'] as $bound => $exclusive) {
            if (($constraints[$exclusive] ?? null) === true && isset($constraints[$bound])) {
                $constraints[$exclusive] = $constraints[$bound];
                unset($constraints[$bound]);
            } elseif (! is_int($constraints[$exclusive] ?? null) && ! is_float($constraints[$exclusive] ?? null)) {
                unset($constraints[$exclusive]);
            }
        }

        $itemEnum = $constraints['items']['enum'] ?? null;
        unset($constraints['items']);

        if (is_array($itemEnum) && $itemEnum) {
            $constraints['items'] = ['enum' => array_map('strval', array_values($itemEnum))];
        }

        if (isset($constraints['enum']) && is_array($constraints['enum'])) {
            $constraints['enum'] = array_map('strval', array_values($constraints['enum']));
        }

        if (isset($constraints['pattern'])) {
            $pattern = RuleHints::jsPattern($constraints['pattern'], (string) ($constraints['patternFlags'] ?? ''));

            if ($pattern === null) {
                unset($constraints['pattern'], $constraints['patternFlags']);
            } else {
                $constraints['pattern'] = $pattern;
            }
        }

        // json-api-server types report unset constraints as null, and zero minimums and `false` flags by default.
        return array_filter($constraints, fn ($value, $key) => $value !== null
            && $value !== false
            && $value !== []
            && ! (in_array($key, ['minLength', 'minItems'], true) && $value === 0), ARRAY_FILTER_USE_BOTH);
    }

    /**
     * One mode's constraints: the type's, overridden by what the rules say.
     *
     * @param array<string, mixed> $type
     * @param list<mixed> $rules
     * @return array<string, mixed>
     */
    protected function constraints(array $type, array $rules, string $kind): array
    {
        $hints = RuleHints::parse($rules, $kind);

        // Requirements and nullability have their own descriptor keys.
        unset($hints['required'], $hints['nullable']);

        // Every pattern must match, the type's and the rules' alike.
        $patterns = [];

        foreach ([$type, $hints] as $source) {
            if (isset($source['pattern'])) {
                $patterns[] = isset($source['patternFlags'])
                    ? ['pattern' => $source['pattern'], 'flags' => $source['patternFlags']]
                    : ['pattern' => $source['pattern']];
            }

            array_push($patterns, ...($source['patterns'] ?? []));
        }

        $keys = ['pattern' => null, 'patternFlags' => null, 'patterns' => null];

        return array_merge(
            array_diff_key($type, $keys),
            array_diff_key($hints, $keys),
            RuleHints::patternHints($patterns)
        );
    }

    /**
     * The field's custom validation messages (`validationMessages()`) for the
     * rules that apply in one mode, keyed by the constraint each rule produced.
     * Message keys may be `{field}.{rule}` or a bare `{rule}`, as in Laravel.
     * Laravel's placeholders are filled in where the rule or constraints give
     * them; a message left with an unresolved placeholder is dropped.
     *
     * @param list<mixed> $rules
     * @param array<string, mixed> $constraints
     * @return array<string, string>
     */
    protected function messages(Field $field, Context $context, array $rules, array $constraints, bool $required, string $label = ''): array
    {
        if (! method_exists($field, 'getValidationMessages')) {
            return [];
        }

        try {
            $custom = $field->getValidationMessages($context->withField($field));
            $attributes = method_exists($field, 'getValidationAttributes') ? $field->getValidationAttributes($context->withField($field)) : [];
        } catch (Throwable) {
            return [];
        }

        $attribute = is_string($attributes[$field->name] ?? null) ? $attributes[$field->name] : StrHelper::lower($label);
        $min = $constraints['minLength'] ?? $constraints['minimum'] ?? $constraints['minItems'] ?? null;
        $max = $constraints['maxLength'] ?? $constraints['maximum'] ?? $constraints['maxItems'] ?? null;

        $common = [
            ':attribute' => $attribute,
            ':Attribute' => ucfirst($attribute),
            ':ATTRIBUTE' => StrHelper::upper($attribute),
            ':min' => $min === null ? null : (string) $min,
            ':max' => $max === null ? null : (string) $max,
            ':size' => $min !== null && $min === $max ? (string) $min : null,
        ];

        $parameters = RuleHints::ruleParameters($rules);
        $other = fn (string $name) => is_string($attributes[$name = RuleHints::fieldName($name)] ?? null)
            ? $attributes[$name]
            : str_replace('_', ' ', StrHelper::snake($name));
        $names = RuleHints::ruleNames($rules);
        $messages = [];

        foreach ($custom as $key => $message) {
            if (! is_string($key) || ! is_string($message)) {
                continue;
            }

            if (str_starts_with($key, $field->name.'.')) {
                $rule = substr($key, strlen($field->name) + 1);
            } elseif (! str_contains($key, '.')) {
                $rule = $key;
            } else {
                continue;
            }

            if (! in_array($rule, $names, true)) {
                continue;
            }

            foreach (RuleHints::constraintKeys($rule) as $constraint) {
                $present = match ($constraint) {
                    'required' => $required,
                    'pattern' => isset($constraints['pattern']) || isset($constraints['patterns']),
                    default => isset($constraints[$constraint]),
                };

                if (! $present || isset($messages[$constraint])) {
                    continue;
                }

                $text = strtr($this->translate($message) ?? $message, array_filter(
                    $this->rulePlaceholders($rule, $parameters[$rule] ?? [], $other) + $common,
                    fn ($value) => $value !== null
                ));

                // An unresolved placeholder would show raw: the client's generic message is better.
                if (! preg_match('/(?<![\w:]):[A-Za-z][A-Za-z_]*/', $text)) {
                    $messages[$constraint] = $text;
                }
            }
        }

        return $messages;
    }

    /**
     * Laravel's placeholders for one rule, from its own parameters.
     *
     * @param list<string> $parameters
     * @param callable(string): string $other the display name of another field
     * @return array<string, string|null>
     */
    protected function rulePlaceholders(string $rule, array $parameters, callable $other): array
    {
        $first = $parameters[0] ?? null;

        return match ($rule) {
            'min' => [':min' => $first],
            'max' => [':max' => $first],
            'size' => [':size' => $first],
            'digits' => [':digits' => $first],
            'between', 'digits_between' => [':min' => $first, ':max' => $parameters[1] ?? null],
            'gt', 'lt', 'gte', 'lte', 'multiple_of' => [':value' => is_numeric($first) ? $first : null],
            'in' => [':values' => $parameters ? implode(', ', $parameters) : null],
            'required_if', 'required_unless' => [
                ':other' => $first === null ? null : $other($first),
                ':value' => $parameters[1] ?? null,
                ':values' => count($parameters) > 1 ? implode(', ', array_slice($parameters, 1)) : null,
            ],
            'required_with', 'required_with_all', 'required_without', 'required_without_all' => [
                ':values' => $parameters ? implode(' / ', array_map($other, $parameters)) : null,
            ],
            'required_if_accepted', 'required_if_declined' => [':other' => $first === null ? null : $other($first)],
            default => [],
        };
    }

    /**
     * Only a static default (`->default('x')`) is published. A closure default is computed
     * server-side and may read data the actor cannot see, so it is never evaluated here.
     */
    protected function defaultValue(Field $field, string $kind): mixed
    {
        if (! in_array($kind, ['string', 'boolean', 'integer', 'number'], true)
            || ! method_exists($field, 'hasStaticDefault') || ! method_exists($field, 'getStaticDefault')
            || ! $field->hasStaticDefault()) {
            return null;
        }

        $value = $field->getStaticDefault();

        return is_scalar($value) ? $value : null;
    }
}
