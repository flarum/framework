<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Audit\Tests\integration;

use Flarum\Group\Group;
use Illuminate\Support\Arr;
use PHPUnit\Framework\Attributes\Test;

class CoreGroupTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->prepareDatabase([
            Group::class => [
                ['id' => 100, 'name_singular' => 'Tester', 'name_plural' => 'Testers'],
            ],
        ]);
    }

    #[Test]
    public function created()
    {
        $response = $this->sendSuccessfulRequest('POST', '/api/groups', [
            'json' => [
                'data' => [
                    'type' => 'groups',
                    'attributes' => [
                        'nameSingular' => 'Wizard',
                        'namePlural' => 'Wizards',
                    ],
                ],
            ],
        ], 201);

        $id = Arr::get(json_decode($response->getBody()->getContents(), true), 'data.id');

        $this->assertLogExists('group.created', [
            'group_id' => (int) $id,
            'name' => 'Wizard',
        ]);
    }

    #[Test]
    public function renamed()
    {
        $this->sendSuccessfulRequest('PATCH', '/api/groups/100', [
            'json' => [
                'data' => [
                    'type' => 'groups',
                    'attributes' => [
                        'nameSingular' => 'Examiner',
                        'namePlural' => 'Examiners',
                    ],
                ],
            ],
        ]);

        $this->assertLogExists('group.renamed', [
            'group_id' => 100,
            'old_name' => 'Tester',
            'new_name' => 'Examiner',
        ]);
    }

    #[Test]
    public function deleted()
    {
        $this->sendSuccessfulRequest('DELETE', '/api/groups/100', [], 204);

        $this->assertLogExists('group.deleted', [
            'group_id' => 100,
            'name' => 'Tester',
        ]);
    }
}
