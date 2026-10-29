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
use Flarum\Locale\TranslatorInterface;
use Illuminate\Support\Str as StrHelper;
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
 * is flagged `conditional` (spec D3).
 *
 * @internal Not part of the extension API; use the extenders.
 */
class FieldDescriber
{
    protected const array CONSTRAINT_KEYS = [
        'minLength', 'maxLength', 'pattern', 'patternFlags', 'enum', 'format',
        'minimum', 'maximum', 'minItems', 'maxItems',
    ];

    public function __construct(
        protected TranslatorInterface $translator
    ) {
    }

    /**
     * @param array{create: DescribeContext, update: DescribeContext, show: DescribeContext, index: DescribeContext} $contexts
     * @param array<string, true> $sortable names of sorts visible to the actor
     * @param list<'create'|'update'> $writeModes the write modes the actor has at type level;
     *                                           create is dropped when the actor cannot create
     * @return array<string, mixed>|null
     */
    public function describe(Field $field, AbstractDatabaseResource $resource, array $contexts, array $sortable, array $writeModes = ['create', 'update']): ?array
    {
        $ui = method_exists($field, 'getUi') ? $field->getUi() : [];
        $visible = $this->visible($field, $contexts);

        $conditional = false;
        $writable = [];
        $required = [];

        foreach ($writeModes as $mode) {
            [$isWritable, $failed] = $this->writable($field, $contexts[$mode]);

            $conditional = $conditional || $failed;

            if ($isWritable) {
                $writable[$mode] = true;

                // `ui.required` is true, false, or a per-mode map; it overrides the inferred value.
                $override = is_array($ui['required'] ?? null) ? ($ui['required'][$mode] ?? null) : ($ui['required'] ?? null);

                if ($override ?? $this->required($field, $contexts[$mode])) {
                    $required[$mode] = true;
                }
            }
        }

        // A field that cannot be seen and cannot be written is of no use to a client.
        if (! $visible && ! $writable) {
            return null;
        }

        $kind = $this->kind($field);

        $description = [
            'name' => $field->name,
            'kind' => $kind,
            'label' => $this->label($field, $ui['label'] ?? null),
        ];

        if (! $visible) {
            $description['visible'] = false;
        }

        if ($required) {
            $description['required'] = $required;
        }

        if ($writable) {
            $description['writable'] = $writable;
        }

        if ($field->nullable) {
            $description['nullable'] = true;
        }

        if ($conditional) {
            $description['conditional'] = true;
        }

        if ($field instanceof Relationship) {
            // `$collections` is a typed property that a custom relationship may never set.
            $description['relationship'] = ['types' => isset($field->collections) ? array_values($field->collections) : []];
        } else {
            $constraints = $this->constraints($field, $kind);

            if ($constraints) {
                $description['constraints'] = $constraints;
            }

            // A default only fills a create form.
            $default = in_array('create', $writeModes, true)
                ? $this->defaultValue($field, $kind, $contexts['create'])
                : null;

            if ($default !== null) {
                $description['default'] = $default;
            }
        }

        if (isset($sortable[$field->name])) {
            $description['sortable'] = true;
        }

        // The label and required are resolved above; don't send them twice.
        unset($ui['label'], $ui['required']);

        // A placeholder that is an existing translation key is sent translated, like a label.
        if (isset($ui['placeholder']) && is_string($ui['placeholder'])) {
            $ui['placeholder'] = $this->translate($ui['placeholder']) ?? $ui['placeholder'];
        }

        if ($ui) {
            $description['ui'] = $ui;
        }

        return $description;
    }

    /**
     * A field is visible when it would be serialized on at least one read endpoint.
     *
     * Fails closed: a mode whose check throws does not count, so a field whose
     * every read check throws is not visible.
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
     * @return array{bool, bool} [writable, conditional]
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
     * Only the unconditional `required` rule counts. `required_without`,
     * `required_with`, `required_if` and friends depend on other input, so the
     * server stays the judge; declare `ui.required` to override.
     */
    protected function required(Field $field, Context $context): bool
    {
        if (! method_exists($field, 'getValidationRules')) {
            return false;
        }

        try {
            $rules = $field->getValidationRules($context->withField($field))[$field->name] ?? [];
        } catch (Throwable) {
            return false;
        }

        return in_array('required', $rules, true);
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
     * `ui.label`, translated when it is an existing key, otherwise literal;
     * else the humanised field name (`joinTime` becomes "Join time").
     */
    protected function label(Field $field, mixed $uiLabel): string
    {
        if (is_string($uiLabel) && $uiLabel !== '') {
            return $this->translate($uiLabel) ?? $uiLabel;
        }

        return ucfirst(str_replace('_', ' ', StrHelper::snake($field->name)));
    }

    protected function translate(string $key): ?string
    {
        // Only key-shaped strings: a literal label must not reach the message formatter.
        if (! preg_match('/^[a-z0-9_-]+(\.[a-z0-9_-]+)+$/i', $key)) {
            return null;
        }

        $translation = $this->translator->trans($key);

        return $translation !== $key ? $translation : null;
    }

    /**
     * @return array<string, mixed>
     */
    protected function constraints(Field $field, string $kind): array
    {
        $constraints = [];

        if ($field instanceof Attribute && $field->type) {
            $constraints = array_intersect_key($field->type->schema(), array_flip(self::CONSTRAINT_KEYS));

            // json-api-server types report unset constraints as null, and a zero minimum length.
            $constraints = array_filter($constraints, fn ($value) => $value !== null);

            if (($constraints['minLength'] ?? null) === 0) {
                unset($constraints['minLength']);
            }
        }

        if (method_exists($field, 'getRules')) {
            $rules = [];

            foreach ($field->getRules() as $entry) {
                if (is_string($entry['rule'])) {
                    $rules[] = $entry['rule'];
                }
            }

            $hints = RuleHints::parse($rules, $kind);
            unset($hints['required']);

            // A rule pattern replaces the type's, together with its flags.
            if (isset($hints['pattern'])) {
                unset($constraints['patternFlags']);
            }

            $constraints = array_merge($constraints, $hints);
        }

        return $constraints;
    }

    protected function defaultValue(Field $field, string $kind, Context $context): mixed
    {
        if (! in_array($kind, ['string', 'boolean', 'integer', 'number'], true) || ! $field->default) {
            return null;
        }

        try {
            $value = ($field->default)($context->withField($field));
        } catch (Throwable) {
            return null;
        }

        return is_scalar($value) ? $value : null;
    }
}
