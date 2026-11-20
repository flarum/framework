<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\api\validation;

use Flarum\Api\Resource\GroupResource;
use Flarum\Api\Schema;
use Flarum\Extend;
use Flarum\Group\Group;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use PHPUnit\Framework\Attributes\Test;
use Tobyz\JsonApiServer\Context;

class MutateDataBeforeValidationTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extend(new Extend\ApiResource(MutatingGroupResource::class));
        $this->extend(new Extend\ApiResource(LowercasingGroupResource::class));
    }

    private function create(string $code): int
    {
        return $this->createLowercased($code)->getStatusCode();
    }

    private function createLowercased(string $code, string $field = 'code', string $type = 'mutating-groups'): \Psr\Http\Message\ResponseInterface
    {
        return $this->send(
            $this->request('POST', '/api/'.$type, [
                'authenticatedAs' => 1,
                'json' => [
                    'data' => [
                        'type' => $type,
                        'attributes' => ['nameSingular' => 'Tester', 'namePlural' => 'Testers', $field => $code],
                    ],
                ],
            ])
        );
    }

    #[Test]
    public function the_mutated_data_is_validated()
    {
        // Only valid once the resource lowercases it.
        $this->assertSame(201, $this->create('ABC'));
    }

    #[Test]
    public function the_rule_still_applies_to_the_mutated_data()
    {
        $this->assertSame(422, $this->create('XYZ'));
    }

    #[Test]
    public function the_mutated_data_is_what_is_saved()
    {
        $response = $this->createLowercased('ModGroup', 'nameSingular', 'lowercasing-groups');

        $this->assertSame(201, $response->getStatusCode(), (string) $response->getBody());
        $this->assertSame('modgroup', Group::query()->latest('id')->value('name_singular'));
    }

    #[Test]
    public function unique_is_checked_against_the_mutated_value()
    {
        $this->assertSame(201, $this->createLowercased('ModGroup', 'nameSingular', 'lowercasing-groups')->getStatusCode());

        $response = $this->createLowercased('MODGROUP', 'nameSingular', 'lowercasing-groups');

        $this->assertSame(422, $response->getStatusCode(), (string) $response->getBody());
        $this->assertSame(1, Group::query()->where('name_singular', 'modgroup')->count());
    }
}

/**
 * Lowercases `nameSingular`, which must be unique: the save and the
 * `unique` check both see the lowercased value.
 */
class LowercasingGroupResource extends GroupResource
{
    public function type(): string
    {
        return 'lowercasing-groups';
    }

    public function fields(): array
    {
        return array_map(
            fn ($field) => $field->name === 'nameSingular' ? $field->rule('unique:groups,name_singular') : $field,
            parent::fields()
        );
    }

    public function mutateDataBeforeValidation(Context $context, array $data): array
    {
        if (isset($data['attributes']['nameSingular'])) {
            $data['attributes']['nameSingular'] = strtolower($data['attributes']['nameSingular']);
        }

        return $data;
    }
}

class MutatingGroupResource extends GroupResource
{
    public function type(): string
    {
        return 'mutating-groups';
    }

    public function fields(): array
    {
        return [
            ...parent::fields(),
            Schema\Str::make('code')
                ->writable()
                ->rule('in:abc')
                ->set(fn () => null),
        ];
    }

    public function mutateDataBeforeValidation(Context $context, array $data): array
    {
        if (isset($data['attributes']['code'])) {
            $data['attributes']['code'] = strtolower($data['attributes']['code']);
        }

        return $data;
    }
}
