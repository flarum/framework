<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\api\resource_schemas;

use Flarum\Api\Context;
use Flarum\Api\Describe\DescribeContext;
use Flarum\Api\Describe\ResourceDescriber;
use Flarum\Api\Endpoint;
use Flarum\Api\JsonApi;
use Flarum\Api\Resource\AbstractDatabaseResource;
use Flarum\Api\Resource\GroupResource;
use Flarum\Api\Schema;
use Flarum\Api\Sort\SortColumn;
use Flarum\Extend;
use Flarum\Group\Group;
use Flarum\Locale\LocaleManager;
use Flarum\Testing\integration\TestCase;
use Flarum\User\Guest;
use Flarum\User\User;
use InvalidArgumentException;
use Laminas\Diactoros\ServerRequest;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tobyz\JsonApiServer\Schema\Field\Relationship;

class InferenceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->extend(
            (new Extend\ApiResource(ScratchGroupResource::class))->describable(),
            (new Extend\ApiResource(ScratchDeletableGroupResource::class))->describable(),
            (new Extend\ApiResource(ScratchRecordingGroupResource::class))->describable(),
            (new Extend\ApiResource(ScratchFullUserResource::class))->describable(),
            (new Extend\ApiResource(ScratchLockedGroupResource::class))->describable(),
            (new Extend\ApiResource(ScratchClosedGroupResource::class))->describable(),
            (new Extend\ApiResource(ScratchRequestRecordingGroupResource::class))->describable(),
        );

        ScratchRequestRecordingGroupResource::$seen = [];

        ScratchRecordingGroupResource::$newModelCalls = 0;
    }

    private function describe(string $type = 'scratch-groups', ?User $actor = null): array
    {
        /** @var JsonApi $api */
        $api = $this->app()->getContainer()->make(JsonApi::class);

        return $this->app()->getContainer()->make(ResourceDescriber::class)->describe(
            $api->getResource($type),
            $actor ?? User::find(1),
            new ServerRequest
        );
    }

    private function field(array $descriptor, string $name): ?array
    {
        foreach ($descriptor['fields'] as $field) {
            if ($field['name'] === $name) {
                return $field;
            }
        }

        return null;
    }

    #[Test]
    public function a_field_whose_visibility_throws_is_absent()
    {
        $this->assertNull($this->field($this->describe(), 'visibleThrows'));
    }

    #[Test]
    public function a_field_whose_writability_throws_is_conditional_and_writable()
    {
        $field = $this->field($this->describe(), 'writableThrows');

        $this->assertNotNull($field);
        $this->assertTrue($field['conditional']);
        $this->assertTrue($field['writable']['update']);
    }

    #[Test]
    public function a_create_only_field_reports_create_only()
    {
        $field = $this->field($this->describe(), 'createOnly');

        $this->assertSame(['create' => true], $field['writable']);
    }

    #[Test]
    public function enum_constraints_are_reported()
    {
        $field = $this->field($this->describe(), 'flavour');

        $this->assertSame(['a', 'b'], $field['constraints']['enum']);
    }

    #[Test]
    public function a_hidden_but_writable_field_is_reported_as_not_visible()
    {
        $field = $this->field($this->describe(), 'secret');

        $this->assertFalse($field['visible']);
        $this->assertTrue($field['writable']['create']);
    }

    #[Test]
    public function defaults_are_omitted()
    {
        $descriptor = $this->describe();

        $this->assertNoVisibleTrue($descriptor);
        $this->assertArrayNotHasKey('visible', $this->field($descriptor, 'plain'));
        $this->assertArrayNotHasKey('writable', $this->field($descriptor, 'plain'));
    }

    #[Test]
    public function the_schema_version_is_always_emitted()
    {
        $this->assertSame(1, $this->describe()['schemaVersion']);
    }

    #[Test]
    public function closures_see_the_actor_but_not_the_describe_requests_query_body_or_route()
    {
        /** @var JsonApi $api */
        $api = $this->app()->getContainer()->make(JsonApi::class);

        $request = (new ServerRequest)
            ->withQueryParams(['filter' => ['q' => 'x'], 'page' => ['limit' => 5]])
            ->withParsedBody(['data' => ['type' => 'groups']])
            ->withAttribute('routeParameters', ['id' => '1']);

        $this->app()->getContainer()->make(ResourceDescriber::class)->describe(
            $api->getResource('scratch-request-recording-groups'),
            User::find(1),
            $request
        );

        $this->assertNotEmpty(ScratchRequestRecordingGroupResource::$seen);

        foreach (ScratchRequestRecordingGroupResource::$seen as $seen) {
            $this->assertSame([], $seen['query']);
            $this->assertNull($seen['body']);
            $this->assertNull($seen['route']);
            $this->assertSame(1, $seen['actor']);
        }
    }

    #[Test]
    public function closures_receive_a_describe_context_with_the_mode()
    {
        $field = $this->field($this->describe(), 'describeOnly');

        $this->assertSame(['update' => true], $field['writable']);
    }

    #[Test]
    public function a_model_dependent_closure_is_evaluated_against_the_synthetic_model()
    {
        $field = $this->field($this->describe(), 'existingOnly');

        $this->assertSame(['update' => true], $field['writable']);
    }

    #[Test]
    public function a_resource_not_opted_in_is_not_describable()
    {
        $this->expectException(InvalidArgumentException::class);

        $this->describe('groups');
    }

    #[Test]
    public function an_extension_can_opt_in_a_resource_it_does_not_own()
    {
        $this->extend((new Extend\ApiResource(GroupResource::class))->describable());

        $this->assertSame('/groups', $this->describe('groups')['endpoint']);
    }

    #[Test]
    public function a_field_whose_visibility_throws_is_hidden_but_reported_when_writable()
    {
        $field = $this->field($this->describe(), 'visibleThrowsWritable');

        $this->assertNotNull($field);
        $this->assertFalse($field['visible']);
        $this->assertSame(['create' => true, 'update' => true], $field['writable']);
    }

    #[Test]
    public function a_field_visible_in_one_read_mode_is_visible_even_if_another_throws()
    {
        $field = $this->field($this->describe(), 'showThrowsIndexVisible');

        $this->assertNotNull($field);
        $this->assertArrayNotHasKey('visible', $field);
    }

    #[Test]
    public function no_null_value_appears_anywhere_in_the_descriptor()
    {
        $this->assertNoNull($this->describe());
    }

    #[Test]
    public function default_type_schema_values_are_not_reported_as_constraints()
    {
        $this->assertArrayNotHasKey('constraints', $this->field($this->describe(), 'createOnly'));
        $this->assertArrayNotHasKey('constraints', $this->field($this->describe(), 'plain'));
    }

    #[Test]
    public function integer_min_and_max_rules_are_reported_as_minimum_and_maximum()
    {
        $field = $this->field($this->describe(), 'rank');

        $this->assertSame(['minimum' => 1, 'maximum' => 9], $field['constraints']);
    }

    #[Test]
    public function the_url_rule_is_reported_as_the_url_format()
    {
        $this->assertSame(['format' => 'url'], $this->field($this->describe(), 'website')['constraints']);
    }

    #[Test]
    public function regex_flags_are_reported_as_pattern_flags()
    {
        $this->assertSame(
            ['pattern' => '^[a-z]+$', 'patternFlags' => 'i'],
            $this->field($this->describe(), 'slug')['constraints']
        );
    }

    #[Test]
    public function a_conditionally_required_rule_does_not_make_a_field_required()
    {
        $field = $this->field($this->describe(), 'partner');

        $this->assertArrayNotHasKey('required', $field);
        $this->assertSame(['create' => true], $this->field($this->describe(), 'mandatory')['required']);
    }

    #[Test]
    public function required_on_update_is_required_on_update_only()
    {
        $this->assertSame(['update' => true], $this->field($this->describe(), 'mandatoryOnUpdate')['required'] ?? null);
    }

    #[Test]
    public function required_without_counts_when_every_alternative_is_hidden_or_undescribed()
    {
        $descriptor = $this->describe();

        $this->assertSame(['create' => true], $this->field($descriptor, 'withoutHidden')['required'] ?? null);
        $this->assertSame(['create' => true, 'update' => true], $this->field($descriptor, 'withoutUndescribed')['required'] ?? null);
        $this->assertSame(['create' => true, 'update' => true], $this->field($descriptor, 'withoutAllHidden')['required'] ?? null);
    }

    #[Test]
    public function required_without_does_not_count_when_an_alternative_is_offered()
    {
        $this->assertArrayNotHasKey('required', $this->field($this->describe(), 'withoutOffered'));
    }

    #[Test]
    public function a_relationship_without_collections_does_not_fatal()
    {
        $field = $this->field($this->describe(), 'untyped');

        $this->assertSame('toOne', $field['kind']);
        $this->assertSame(['types' => []], $field['relationship']);
    }

    #[Test]
    public function a_ui_label_that_is_a_translation_key_is_translated()
    {
        $field = $this->field($this->describe(), 'labelKey');

        $this->assertSame('Joined', $field['label']);
        $this->assertSame(['column' => true], $field['ui']);
    }

    #[Test]
    public function a_ui_label_that_is_not_a_translation_key_is_used_literally()
    {
        $field = $this->field($this->describe(), 'labelLiteral');

        $this->assertSame('Custom label', $field['label']);
        $this->assertArrayNotHasKey('ui', $field);
    }

    #[Test]
    public function a_description_that_is_a_translation_key_is_the_label()
    {
        $this->assertSame('Display name', $this->field($this->describe(), 'described')['label']);
    }

    #[Test]
    public function a_description_that_is_not_a_translation_key_is_not_the_label()
    {
        $this->assertSame('Described literal', $this->field($this->describe(), 'describedLiteral')['label']);
    }

    #[Test]
    public function the_conventional_resource_key_is_the_label_when_it_exists()
    {
        $file = sys_get_temp_dir().'/flarum-describe-'.uniqid().'.yml';
        file_put_contents($file, "core:\n  admin:\n    resource:\n      scratch-groups:\n        join_time: Joined at\n");

        try {
            $this->app()->getContainer()->make(LocaleManager::class)->addTranslations('en', $file);

            $this->assertSame('Joined at', $this->field($this->describe(), 'joinTime')['label']);
        } finally {
            @unlink($file);
        }
    }

    #[Test]
    public function the_humanised_name_is_the_label_otherwise()
    {
        $descriptor = $this->describe();

        $this->assertSame('Join time', $this->field($descriptor, 'joinTime')['label']);
        $this->assertSame('Plain', $this->field($descriptor, 'plain')['label']);
    }

    #[Test]
    public function every_field_has_a_label()
    {
        foreach ($this->describe()['fields'] as $field) {
            $this->assertIsString($field['label'] ?? null, "Field [{$field['name']}] has no label.");
            $this->assertNotSame('', $field['label']);
        }
    }

    #[Test]
    public function new_model_is_not_called_for_a_guest()
    {
        $this->describe('scratch-recording-groups', new Guest);

        $this->assertSame(0, ScratchRecordingGroupResource::$newModelCalls);
    }

    #[Test]
    public function new_model_is_called_for_an_authenticated_actor()
    {
        $this->describe('scratch-recording-groups');

        $this->assertGreaterThan(0, ScratchRecordingGroupResource::$newModelCalls);
    }

    #[Test]
    public function the_create_capability_is_checked_without_a_model()
    {
        $this->assertTrue($this->describe('scratch-recording-groups')['capabilities']['create']);
    }

    #[Test]
    public function a_resource_with_a_delete_endpoint_has_the_delete_capability()
    {
        $this->assertTrue($this->describe('scratch-deletable-groups')['capabilities']['delete']);
    }

    #[Test]
    public function a_resource_without_a_delete_endpoint_has_no_delete_capability()
    {
        $this->assertArrayNotHasKey('delete', $this->describe()['capabilities']);
    }

    #[Test]
    public function a_guest_has_no_delete_capability()
    {
        $this->assertArrayNotHasKey('delete', $this->describe('scratch-deletable-groups', new Guest)['capabilities']);
    }

    #[Test]
    public function every_top_level_key_is_listed_in_keys()
    {
        $descriptor = $this->describe('scratch-full-users');

        $this->assertSame([], array_diff(array_keys($descriptor), ResourceDescriber::KEYS));
        // The scratch resource produces every optional key, so nothing in KEYS is stale either.
        $this->assertSame([], array_diff(ResourceDescriber::KEYS, array_keys($descriptor)));
    }

    #[Test]
    public function every_top_level_key_reaches_the_http_response()
    {
        $response = $this->send(
            $this->request('GET', '/api/resource-schemas/scratch-full-users', ['authenticatedAs' => 1])
        );

        $body = (string) $response->getBody();

        $this->assertEquals(200, $response->getStatusCode(), $body);

        $attributes = json_decode($body, true)['data']['attributes'];

        foreach (ResourceDescriber::KEYS as $key) {
            $this->assertArrayHasKey($key, $attributes, "[$key] is missing from the response.");
        }

        $this->assertSame('-joinedAt', $attributes['defaultSort']);
        $this->assertSame(['defaultLimit' => 10, 'maxLimit' => 30], $attributes['pagination']);
        $this->assertTrue($attributes['capabilities']['delete']);
    }

    #[Test]
    public function can_describe_follows_the_list_and_create_capabilities()
    {
        /** @var JsonApi $api */
        $api = $this->app()->getContainer()->make(JsonApi::class);
        $describer = $this->app()->getContainer()->make(ResourceDescriber::class);

        $this->assertTrue($describer->canDescribe($api->getResource('scratch-groups'), User::find(1), new ServerRequest));
        // Only creatable without a model: the create capability alone admits.
        $this->assertTrue($describer->canDescribe($api->getResource('scratch-locked-groups'), User::find(1), new ServerRequest));
        $this->assertFalse($describer->canDescribe($api->getResource('scratch-closed-groups'), User::find(1), new ServerRequest));
        // Not opted in.
        $this->assertFalse($describer->canDescribe($api->getResource('groups'), User::find(1), new ServerRequest));
    }

    private function assertNoNull(array $value, string $path = ''): void
    {
        foreach ($value as $key => $item) {
            $this->assertNotNull($item, "A null value was emitted at [$path.$key].");

            if (is_array($item)) {
                $this->assertNoNull($item, "$path.$key");
            }
        }
    }

    private function assertNoVisibleTrue(array $value): void
    {
        foreach ($value as $key => $item) {
            $this->assertFalse($key === 'visible' && $item === true, 'A visible:true key was emitted.');

            if (is_array($item)) {
                $this->assertNoVisibleTrue($item);
            }
        }
    }
}

/**
 * @extends AbstractDatabaseResource<Group>
 */
class ScratchGroupResource extends AbstractDatabaseResource
{
    public function type(): string
    {
        return 'scratch-groups';
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
            Endpoint\Show::make(),
            Endpoint\Index::make(),
        ];
    }

    public function fields(): array
    {
        return [
            Schema\Str::make('visibleThrows')
                ->visible(fn () => throw new RuntimeException('row dependent')),
            Schema\Str::make('writableThrows')
                ->writable(fn () => throw new RuntimeException('row dependent')),
            Schema\Str::make('createOnly')
                ->writableOnCreate(),
            Schema\Str::make('flavour')
                ->in(['a', 'b'])
                ->writable(),
            Schema\Str::make('secret')
                ->hidden()
                ->writableOnCreate(),
            Schema\Integer::make('plain'),
            Schema\Str::make('describeOnly')
                ->writable(fn ($model, Context $context) => $context instanceof DescribeContext && $context->mode() === 'update'),
            Schema\Str::make('existingOnly')
                ->writable(fn (Group $group) => $group->exists),
            Schema\Str::make('visibleThrowsWritable')
                ->visible(fn () => throw new RuntimeException('row dependent'))
                ->writable(),
            Schema\Str::make('showThrowsIndexVisible')
                // Called with ($model, $context) when there is a model (show), ($context) otherwise (index).
                ->visible(fn (...$args) => end($args) instanceof DescribeContext && end($args)->mode() === 'show'
                    ? throw new RuntimeException('row dependent')
                    : true),
            Schema\Integer::make('rank')
                ->min(1)
                ->max(9)
                ->writable(),
            Schema\Str::make('website')
                ->rule('url')
                ->writable(),
            Schema\Str::make('slug')
                ->regex('/^[a-z]+$/i')
                ->writable(),
            Schema\Str::make('partner')
                ->requiredWith(['flavour'], true)
                ->writable(),
            Schema\Str::make('mandatory')
                ->requiredOnCreate()
                ->writable(),
            Schema\Str::make('mandatoryOnUpdate')
                ->requiredOnUpdate()
                ->writable(),
            Schema\Str::make('hiddenAlternative')
                ->hidden()
                ->writableOnCreate()
                ->ui(['widget' => 'hidden']),
            Schema\Str::make('withoutHidden')
                ->requiredOnCreateWithout(['hiddenAlternative'])
                ->writable(),
            Schema\Str::make('withoutOffered')
                ->requiredOnCreateWithout(['hiddenAlternative', 'flavour'])
                ->writable(),
            Schema\Str::make('withoutUndescribed')
                ->requiredWithout(['ghost'], true)
                ->writable(),
            Schema\Str::make('withoutAllHidden')
                ->rule('required_without_all:hiddenAlternative,ghost')
                ->writable(),
            UntypedRelationship::make('untyped'),
            Schema\Str::make('labelKey')
                ->ui(['label' => 'core.admin.users.grid.columns.join_time.title', 'column' => true]),
            Schema\Str::make('labelLiteral')
                ->ui(['label' => 'Custom label']),
            Schema\Str::make('described')
                ->description('core.admin.users.grid.columns.display_name.title'),
            Schema\Str::make('describedLiteral')
                ->description('Free text, not a key'),
            Schema\DateTime::make('joinTime'),
        ];
    }
}

/**
 * A relationship whose `$collections` is never initialised.
 */
class UntypedRelationship extends Relationship
{
}

class ScratchDeletableGroupResource extends ScratchGroupResource
{
    public function type(): string
    {
        return 'scratch-deletable-groups';
    }

    public function endpoints(): array
    {
        return [...parent::endpoints(), Endpoint\Delete::make()];
    }
}

class ScratchRecordingGroupResource extends ScratchGroupResource
{
    public static int $newModelCalls = 0;

    public function type(): string
    {
        return 'scratch-recording-groups';
    }

    public function endpoints(): array
    {
        return [
            Endpoint\Create::make()
                // Type-level check: the create capability must be evaluated without a model.
                ->visible(fn (Context $context) => $context->model === null),
            Endpoint\Update::make(),
            Endpoint\Show::make(),
            Endpoint\Index::make(),
        ];
    }

    public function newModel(\Tobyz\JsonApiServer\Context $context): object
    {
        static::$newModelCalls++;

        return parent::newModel($context);
    }
}

/**
 * Produces every optional top-level key: pagination, defaultSort, search, sorts, filters and delete.
 *
 * @extends AbstractDatabaseResource<User>
 */
class ScratchFullUserResource extends AbstractDatabaseResource
{
    public function type(): string
    {
        return 'scratch-full-users';
    }

    public function model(): string
    {
        return User::class;
    }

    public function endpoints(): array
    {
        return [
            Endpoint\Create::make(),
            Endpoint\Show::make(),
            Endpoint\Delete::make(),
            Endpoint\Index::make()
                ->defaultSort('-joinedAt')
                ->paginate(10, 30),
        ];
    }

    public function fields(): array
    {
        return [
            Schema\Str::make('username'),
        ];
    }

    public function sorts(): array
    {
        return [
            SortColumn::make('joinedAt'),
        ];
    }
}

/**
 * Index is closed; Create is open only at type level (no model).
 */
class ScratchLockedGroupResource extends ScratchGroupResource
{
    public function type(): string
    {
        return 'scratch-locked-groups';
    }

    public function endpoints(): array
    {
        return [
            Endpoint\Create::make()
                ->visible(fn (Context $context) => $context->model === null),
            Endpoint\Index::make()
                ->visible(false),
        ];
    }
}

/**
 * Neither listable nor creatable.
 */
class ScratchClosedGroupResource extends ScratchGroupResource
{
    public function type(): string
    {
        return 'scratch-closed-groups';
    }

    public function endpoints(): array
    {
        return [
            Endpoint\Create::make()
                ->visible(false),
            Endpoint\Index::make()
                ->visible(fn () => throw new RuntimeException('fails closed')),
            Endpoint\Show::make(),
        ];
    }
}

class ScratchRequestRecordingGroupResource extends ScratchGroupResource
{
    /** @var list<array{query: array, body: mixed, route: mixed, actor: int|null}> */
    public static array $seen = [];

    public function type(): string
    {
        return 'scratch-request-recording-groups';
    }

    public function fields(): array
    {
        return [
            ...parent::fields(),
            Schema\Str::make('requestProbe')
                ->visible(function (Context $context) {
                    static::$seen[] = [
                        'query' => $context->request->getQueryParams(),
                        'body' => $context->request->getParsedBody(),
                        'route' => $context->request->getAttribute('routeParameters'),
                        'actor' => \Flarum\Http\RequestUtil::getActor($context->request)->id,
                    ];

                    return true;
                }),
        ];
    }
}
