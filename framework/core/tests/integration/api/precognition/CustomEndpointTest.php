<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\api\precognition;

require_once __DIR__.'/PrecognitionTestHelpers.php';

use Flarum\Api\Endpoint\Update;
use Flarum\Api\Resource\GroupResource;
use Flarum\Extend;
use Flarum\Group\Group;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use PHPUnit\Framework\Attributes\Test;
use Tobyz\JsonApiServer\Context;

class CustomEndpointTest extends TestCase
{
    use RetrievesAuthorizedUsers;
    use PrecognitionTestHelpers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extend(
            (new Extend\ApiResource(GroupResource::class))
                ->endpoints(fn () => [
                    Update::make('profile.update')->route('PATCH', '/{id}/profile')->admin(),
                    UpdateWithValidationOverride::make('override')->route('PATCH', '/{id}/override')->admin(),
                ])
        );
    }

    protected function rename(string $name): array
    {
        return ['data' => ['type' => 'groups', 'id' => '4', 'attributes' => ['nameSingular' => $name, 'namePlural' => 'Renamed']]];
    }

    #[Test]
    public function endpoints_with_dotted_names_support_precognition()
    {
        $invalid = $this->send($this->precognitive($this->jsonRequest('PATCH', '/api/groups/4/profile', $this->rename(''), 1)));
        $valid = $this->send($this->precognitive($this->jsonRequest('PATCH', '/api/groups/4/profile', $this->rename('Renamed'), 1)));

        $this->assertEquals(422, $invalid->getStatusCode(), (string) $invalid->getBody());
        $this->assertEquals(204, $valid->getStatusCode(), (string) $valid->getBody());
        $this->assertEquals('Mod', Group::findOrFail(4)->name_singular);
    }

    #[Test]
    public function endpoints_overriding_assert_data_valid_keep_working()
    {
        $precognitive = $this->send($this->precognitive($this->jsonRequest('PATCH', '/api/groups/4/override', $this->rename('Renamed'), 1)));

        $this->assertEquals(204, $precognitive->getStatusCode(), (string) $precognitive->getBody());
        $this->assertEquals('Mod', Group::findOrFail(4)->name_singular);

        $normal = $this->send($this->jsonRequest('PATCH', '/api/groups/4/override', $this->rename('Renamed'), 1));

        $this->assertEquals(200, $normal->getStatusCode(), (string) $normal->getBody());
        $this->assertEquals('Renamed', Group::findOrFail(4)->name_singular);
    }
}

class UpdateWithValidationOverride extends Update
{
    protected function assertDataValid(Context $context, array $data): void
    {
        parent::assertDataValid($context, $data);
    }
}
