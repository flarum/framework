<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\api\resource_schemas;

use Flarum\Api\Context;
use Flarum\Api\Describe\ResourceDescriber;
use Flarum\Api\Endpoint;
use Flarum\Api\JsonApi;
use Flarum\Api\Resource\AbstractDatabaseResource;
use Flarum\Api\Schema;
use Flarum\Extend;
use Flarum\Group\Filter\GroupSearcher;
use Flarum\Group\Group;
use Flarum\Search\Database\DatabaseSearchDriver;
use Flarum\Search\Filter\DescribesFilter;
use Flarum\Search\Filter\FilterInterface;
use Flarum\Search\SearchState;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use Illuminate\Validation\Rule;
use Laminas\Diactoros\ServerRequest;
use Monolog\Handler\TestHandler;
use Monolog\Logger;
use PHPUnit\Framework\Attributes\Test;

/**
 * Descriptor keys of schema version 2: type labels, row abilities, enum labels,
 * custom messages, live validation, the typed `ui` hints and filter labels.
 */
class DescriptorHintsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->extend(
            new Extend\Locales(__DIR__.'/fixtures/locale'),
            (new Extend\ApiResource(ScratchHintedResource::class))->describable(),
            (new Extend\ApiResource(ScratchModeratedResource::class))
                ->describable()
                ->rowAbilities(['edit' => 'canModerate', 'delete' => null]),
            (new Extend\SearchDriver(DatabaseSearchDriver::class))
                ->addFilter(GroupSearcher::class, ScratchColourFilter::class)
                ->addFilter(GroupSearcher::class, ScratchConventionFilter::class)
                ->addFilter(GroupSearcher::class, ScratchUnlabelledThingFilter::class),
        );
    }

    private function describe(string $type = 'scratch-hinted'): array
    {
        /** @var JsonApi $api */
        $api = $this->app()->getContainer()->make(JsonApi::class);

        return $this->app()->getContainer()->make(ResourceDescriber::class)->describe(
            $api->getResource($type),
            User::find(1),
            new ServerRequest
        );
    }

    private function field(array $descriptor, string $name): array
    {
        return array_column($descriptor['fields'], null, 'name')[$name];
    }

    private function filter(array $descriptor, string $key): array
    {
        return array_column($descriptor['filters'], null, 'key')[$key];
    }

    #[Test]
    public function the_type_label_is_translated_by_convention()
    {
        $this->assertSame(['one' => 'Hinted thing', 'other' => 'Hinted things'], $this->describe()['label']);
    }

    #[Test]
    public function the_type_label_falls_back_to_the_humanised_type()
    {
        $this->assertSame(['one' => 'Scratch moderated', 'other' => 'Scratch moderated'], $this->describe('scratch-moderated')['label']);
    }

    #[Test]
    public function row_abilities_are_inferred_from_can_edit_and_can_delete()
    {
        $this->assertSame(['edit' => 'canEdit', 'delete' => 'canDelete'], $this->describe()['rowAbilities']);
    }

    #[Test]
    public function row_abilities_can_be_overridden_and_removed_by_the_extender()
    {
        $this->assertSame(['edit' => 'canModerate'], $this->describe('scratch-moderated')['rowAbilities']);
    }

    #[Test]
    public function enum_values_with_a_translation_get_a_label()
    {
        $colour = $this->field($this->describe(), 'colour');

        $this->assertSame(['r', 'g', 'b'], $colour['constraints']['enum']);
        // `b` has no translation: the client shows the raw value.
        $this->assertSame(['r' => 'Red', 'g' => 'Green'], $colour['enumLabels']);
    }

    #[Test]
    public function custom_messages_are_keyed_by_the_constraint_they_explain()
    {
        $code = $this->field($this->describe(), 'code');

        $this->assertSame([
            'pattern' => 'The code must be lowercase letters.',
            'maxLength' => 'Too long',
            'required' => 'Need a code',
        ], $code['constraints']['messages']);
    }

    #[Test]
    public function message_placeholders_are_filled_and_unresolvable_messages_are_dropped()
    {
        $field = $this->field($this->describe(), 'placeholderMessage');

        $this->assertSame([
            'minLength' => 'Placeholder message must be 4 long (4-4).',
            'maxLength' => 'Placeholder message must be 4 long (4-4).',
            'enum' => 'Pick one of abcd, wxyz for placeholder message.',
            'required' => 'Needed when colour is set.',
        ], $field['constraints']['messages']);
    }

    #[Test]
    public function messages_follow_the_rules_of_each_mode()
    {
        $perMode = $this->field($this->describe(), 'perModeMessage');

        $this->assertSame(['maxLength' => 'At most ten'], $perMode['constraints']['messages']);
        $this->assertArrayNotHasKey('messages', $perMode['constraintsOnUpdate']);
    }

    #[Test]
    public function a_message_for_a_rule_that_does_not_apply_is_not_sent()
    {
        $this->assertArrayNotHasKey('messages', $this->field($this->describe(), 'unusedMessage')['constraints'] ?? []);
    }

    #[Test]
    public function rules_only_the_server_can_check_ask_for_live_validation()
    {
        $descriptor = $this->describe();

        $this->assertTrue($this->field($descriptor, 'uniqueName')['ui']['liveValidate']);
        $this->assertTrue($this->field($descriptor, 'requiredByClosure')['ui']['liveValidate']);
        $this->assertTrue($this->field($descriptor, 'customRule')['ui']['liveValidate']);
        $this->assertArrayNotHasKey('ui', $this->field($descriptor, 'plainWritable'));
        // Rules of a field the actor cannot write are never evaluated for them.
        $this->assertArrayNotHasKey('ui', $this->field($descriptor, 'readOnlyUnique'));
    }

    #[Test]
    public function typed_ui_hints_are_described()
    {
        $hinted = $this->field($this->describe(), 'hinted');

        $this->assertSame([
            'column' => 70,
            'hidden' => true,
            'widget' => 'textarea',
            'placeholder' => 'Type here',
            'help' => 'Some help',
            'group' => 'Main settings',
            'priority' => 5,
            'visibleWhen' => [['rule' => 'if', 'fields' => ['colour'], 'values' => ['r']]],
            'markRequired' => true,
        ], $hinted['ui']);
    }

    #[Test]
    public function visible_when_accepts_the_required_when_shape()
    {
        $field = $this->field($this->describe(), 'visibleByShape');

        $this->assertSame([['rule' => 'with', 'fields' => ['code']], ['rule' => 'without', 'fields' => ['colour']]], $field['ui']['visibleWhen']);
    }

    #[Test]
    public function unknown_ui_keys_are_logged_in_debug_mode()
    {
        $log = new TestHandler();
        $this->app()->getContainer()->instance('log', new Logger('test', [$log]));

        $typo = $this->field($this->describe(), 'typo');

        // Passed through all the same.
        $this->assertSame(1, $typo['ui']['colum']);
        $this->assertTrue($log->hasWarningThatContains('Unknown ui hint(s) [colum] on field [typo] of resource [scratch-hinted]'));
        $this->assertCount(1, $log->getRecords());
    }

    #[Test]
    public function filters_get_the_same_named_field_label_and_its_enum_labels()
    {
        $colour = $this->filter($this->describe(), 'colour');

        $this->assertSame('Colour', $colour['label']);
        $this->assertSame(['b' => 'Blue (filter)', 'r' => 'Red', 'g' => 'Green'], $colour['enumLabels']);
        $this->assertTrue($colour['multiple']);
        $this->assertSame('r', $colour['default']);
    }

    #[Test]
    public function filters_get_the_conventional_or_the_humanised_label()
    {
        $descriptor = $this->describe();

        $this->assertSame('Conventional filter', $this->filter($descriptor, 'convention')['label']);
        $this->assertSame('Unlabelled thing', $this->filter($descriptor, 'unlabelledThing')['label']);
    }
}

/**
 * @extends AbstractDatabaseResource<Group>
 */
class ScratchHintedResource extends AbstractDatabaseResource
{
    public function type(): string
    {
        return 'scratch-hinted';
    }

    public function model(): string
    {
        return Group::class;
    }

    public function endpoints(): array
    {
        return [
            Endpoint\Create::make(),
            Endpoint\Update::make(),
            Endpoint\Index::make(),
        ];
    }

    public function fields(): array
    {
        return [
            Schema\Str::make('colour')
                ->in(['r', 'g', 'b'])
                ->writable(),
            Schema\Str::make('code')
                ->required()
                ->regex('/^[a-z]+$/')
                ->rule('max:5')
                ->validationMessages([
                    'code.regex' => 'core.admin.resource.scratch-hinted.message_code',
                    'code.max' => 'Too long',
                    'required' => 'Need a code',
                ])
                ->writable(),
            Schema\Str::make('perModeMessage')
                ->rule('max:10', fn (Context $context) => $context->creating())
                ->rule('min:2')
                ->validationMessages(['perModeMessage.max' => 'At most ten'])
                ->writable(),
            Schema\Str::make('placeholderMessage')
                ->rule('size:4')
                ->rule('in:abcd,wxyz')
                ->rule('required_with:attributes.colour')
                ->rule('regex:/^[a-z]+$/')
                ->validationMessages([
                    'placeholderMessage.size' => ':Attribute must be :size long (:min-:max).',
                    'placeholderMessage.in' => 'Pick one of :values for :attribute.',
                    'placeholderMessage.required_with' => 'Needed when :values is set.',
                    'placeholderMessage.regex' => ':attribute must match :format at 10:30.',
                ])
                ->writable(),
            Schema\Str::make('unusedMessage')
                ->validationMessages(['unusedMessage.regex' => 'Never applies'])
                ->writable(),
            Schema\Str::make('uniqueName')
                ->unique('groups', 'name_singular')
                ->writable(),
            Schema\Str::make('requiredByClosure')
                ->rule(fn () => Rule::requiredIf(fn () => true))
                ->writable(),
            Schema\Str::make('customRule')
                ->rule(fn () => function (string $attribute, mixed $value, \Closure $fail) {
                })
                ->writable(),
            Schema\Str::make('plainWritable')
                ->rule('max:3')
                ->writable(),
            Schema\Str::make('readOnlyUnique')
                ->unique('groups', 'name_singular'),
            Schema\Str::make('hinted')
                ->columnHidden(70)
                ->widget('textarea')
                ->placeholder('Type here')
                ->help('core.admin.resource.scratch-hinted.help_hinted')
                ->group('core.admin.resource.scratch-hinted.group_main')
                ->priority(5)
                ->visibleWhen('if:colour,r')
                ->markRequired(),
            Schema\Str::make('visibleByShape')
                ->visibleWhen([['rule' => 'with', 'fields' => ['code']], 'required_without:colour']),
            Schema\Str::make('typo')
                ->ui(['colum' => 1]),
            Schema\Boolean::make('canEdit')
                ->get(fn () => true),
            Schema\Boolean::make('canDelete')
                ->get(fn () => true),
        ];
    }
}

class ScratchModeratedResource extends ScratchHintedResource
{
    public function type(): string
    {
        return 'scratch-moderated';
    }

    public function fields(): array
    {
        return [
            Schema\Boolean::make('canEdit')
                ->get(fn () => true),
            Schema\Boolean::make('canDelete')
                ->get(fn () => true),
            Schema\Boolean::make('canModerate')
                ->get(fn () => true),
        ];
    }
}

abstract class ScratchNoopFilter implements FilterInterface, DescribesFilter
{
    public function filter(SearchState $state, string|array $value, bool $negate): void
    {
    }
}

class ScratchColourFilter extends ScratchNoopFilter
{
    public function getFilterKey(): string
    {
        return 'colour';
    }

    public function describe(User $actor): ?array
    {
        return ['kind' => 'enum', 'enum' => ['r', 'g', 'b'], 'multiple' => true, 'default' => 'r'];
    }
}

class ScratchConventionFilter extends ScratchNoopFilter
{
    public function getFilterKey(): string
    {
        return 'convention';
    }

    public function describe(User $actor): ?array
    {
        return ['kind' => 'string'];
    }
}

class ScratchUnlabelledThingFilter extends ScratchNoopFilter
{
    public function getFilterKey(): string
    {
        return 'unlabelledThing';
    }

    public function describe(User $actor): ?array
    {
        return ['kind' => 'string'];
    }
}
