<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\api\groups;

use Flarum\Group\Event\Renamed;
use Flarum\Group\Group;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Illuminate\Contracts\Events\Dispatcher;
use PHPUnit\Framework\Attributes\Test;

class UpdateTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    /**
     * @var Renamed[]
     */
    protected array $renamed = [];

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->prepareDatabase([
            Group::class => [
                ['id' => 10, 'name_singular' => 'flarumite', 'name_plural' => 'flarumites'],
            ],
        ]);
    }

    protected function update(array $attributes)
    {
        $this->app()->getContainer()->make(Dispatcher::class)->listen(Renamed::class, function (Renamed $event) {
            $this->renamed[] = $event;
        });

        $response = $this->send(
            $this->request('PATCH', '/api/groups/10', [
                'authenticatedAs' => 1,
                'json' => [
                    'data' => [
                        'type' => 'groups',
                        'id' => '10',
                        'attributes' => $attributes,
                    ],
                ],
            ])
        );

        $this->assertEquals(200, $response->getStatusCode(), (string) $response->getBody());
    }

    #[Test]
    public function renaming_both_names_dispatches_one_renamed_event()
    {
        $this->update(['nameSingular' => 'member', 'namePlural' => 'members']);

        $this->assertCount(1, $this->renamed);
        $this->assertEquals('flarumite', $this->renamed[0]->oldNameSingular);
        $this->assertEquals('flarumites', $this->renamed[0]->oldNamePlural);
        $this->assertEquals('member', $this->renamed[0]->group->name_singular);
        $this->assertEquals('members', $this->renamed[0]->group->name_plural);
    }

    #[Test]
    public function renaming_one_name_while_sending_both_dispatches_one_renamed_event()
    {
        $this->update(['nameSingular' => 'member', 'namePlural' => 'flarumites']);

        $this->assertCount(1, $this->renamed);
        $this->assertEquals('flarumite', $this->renamed[0]->oldNameSingular);
    }

    #[Test]
    public function sending_unchanged_names_dispatches_no_renamed_event()
    {
        $this->update(['nameSingular' => 'flarumite', 'namePlural' => 'flarumites', 'color' => '#123456']);

        $this->assertCount(0, $this->renamed);
    }

    protected function patch(int $id, array $attributes)
    {
        return $this->send(
            $this->request('PATCH', "/api/groups/$id", [
                'authenticatedAs' => 1,
                'json' => ['data' => ['type' => 'groups', 'id' => (string) $id, 'attributes' => $attributes]],
            ])
        );
    }

    #[Test]
    public function the_colour_can_be_changed_without_sending_the_names()
    {
        $response = $this->patch(10, ['color' => '#654321']);

        $this->assertEquals(200, $response->getStatusCode(), (string) $response->getBody());
        $this->assertSame('#654321', Group::find(10)->color);
        $this->assertSame('flarumite', Group::find(10)->name_singular);
    }

    #[Test]
    public function a_built_in_group_keeps_its_stored_names_when_only_its_colour_changes()
    {
        $response = $this->patch(Group::ADMINISTRATOR_ID, ['color' => '#000000']);

        $this->assertEquals(200, $response->getStatusCode(), (string) $response->getBody());

        $group = Group::find(Group::ADMINISTRATOR_ID);
        $this->assertSame('#000000', $group->color);
        // Not the translated "Admin" the API shows.
        $this->assertSame('Admin', $group->name_singular);
        $this->assertSame('Admins', $group->name_plural);
    }

    #[Test]
    public function a_name_cannot_be_emptied()
    {
        $response = $this->patch(10, ['nameSingular' => '']);

        $this->assertEquals(422, $response->getStatusCode(), (string) $response->getBody());
        $this->assertSame('flarumite', Group::find(10)->name_singular);
    }
}
