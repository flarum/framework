<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\api\users;

use Flarum\Group\Group;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use Illuminate\Support\Arr;
use PHPUnit\Framework\Attributes\Test;

class ShowGroupsRelationshipTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->prepareDatabase([
            User::class => [
                $this->normalUser(),
            ],
            Group::class => [
                [
                    'id' => 10,
                    'name_singular' => 'Public',
                    'name_plural' => 'Public',
                    'is_hidden' => 0,
                ],
                [
                    'id' => 11,
                    'name_singular' => 'Hidden',
                    'name_plural' => 'Hidden',
                    'is_hidden' => 1,
                ],
            ],
            'group_user' => [
                ['user_id' => 2, 'group_id' => 10],
                ['user_id' => 2, 'group_id' => 11],
            ],
        ]);
    }

    private function groupRelationshipIds(array $body): array
    {
        return Arr::pluck($body['data']['relationships']['groups']['data'] ?? [], 'id');
    }

    private function includedGroupIds(array $body): array
    {
        $included = $body['included'] ?? [];

        $groups = array_filter($included, fn (array $resource) => ($resource['type'] ?? null) === 'groups');

        return array_values(Arr::pluck($groups, 'id'));
    }

    #[Test]
    public function guest_does_not_see_hidden_groups_in_user_groups_relationship()
    {
        $response = $this->send($this->request('GET', '/api/users/2'));

        $this->assertEquals(200, $response->getStatusCode());
        $body = json_decode($response->getBody()->getContents(), true);

        $this->assertEqualsCanonicalizing(['10'], $this->groupRelationshipIds($body));
        $this->assertNotContains('11', $this->includedGroupIds($body));
    }

    #[Test]
    public function normal_user_does_not_see_hidden_groups_in_user_groups_relationship()
    {
        $response = $this->send(
            $this->request('GET', '/api/users/2', [
                'authenticatedAs' => 2,
            ])
        );

        $this->assertEquals(200, $response->getStatusCode());
        $body = json_decode($response->getBody()->getContents(), true);

        $this->assertEqualsCanonicalizing(['10'], $this->groupRelationshipIds($body));
        $this->assertNotContains('11', $this->includedGroupIds($body));
    }

    #[Test]
    public function admin_sees_hidden_groups_in_user_groups_relationship()
    {
        $response = $this->send(
            $this->request('GET', '/api/users/2', [
                'authenticatedAs' => 1,
            ])
        );

        $this->assertEquals(200, $response->getStatusCode());
        $body = json_decode($response->getBody()->getContents(), true);

        $this->assertEqualsCanonicalizing(['10', '11'], $this->groupRelationshipIds($body));
        $this->assertEqualsCanonicalizing(['10', '11'], $this->includedGroupIds($body));
    }
}
