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
    }

    private function create(string $code): int
    {
        $response = $this->send(
            $this->request('POST', '/api/mutating-groups', [
                'authenticatedAs' => 1,
                'json' => [
                    'data' => [
                        'type' => 'mutating-groups',
                        'attributes' => ['nameSingular' => 'Tester', 'namePlural' => 'Testers', 'code' => $code],
                    ],
                ],
            ])
        );

        return $response->getStatusCode();
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
