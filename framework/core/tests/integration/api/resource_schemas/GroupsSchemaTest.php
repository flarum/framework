<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\api\resource_schemas;

use Flarum\Group\Filter\HiddenFilter;
use Flarum\Group\Group;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use Illuminate\Support\Arr;
use PHPUnit\Framework\Attributes\Test;

/**
 * The groups descriptor, which core describes by default, and the admin page it drives.
 */
class GroupsSchemaTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->prepareDatabase([
            User::class => [
                $this->normalUser(),
            ],
            Group::class => [
                ['id' => 100, 'name_singular' => 'Tester', 'name_plural' => 'Testers', 'color' => '#123456', 'icon' => 'fas fa-flask', 'is_hidden' => 0],
            ],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function describe(int $actorId): array
    {
        $response = $this->send(
            $this->request('GET', '/api/resource-schemas/groups', ['authenticatedAs' => $actorId])
        );

        $body = (string) $response->getBody();

        $this->assertEquals(200, $response->getStatusCode(), $body);

        return json_decode($body, true)['data']['attributes'];
    }

    /**
     * @param array<string, mixed> $descriptor
     * @return array<string, array<string, mixed>>
     */
    protected function fieldsByName(array $descriptor): array
    {
        return array_column($descriptor['fields'], null, 'name');
    }

    #[Test]
    public function groups_are_describable_by_default()
    {
        $descriptor = $this->describe(1);

        $this->assertSame(1, $descriptor['schemaVersion']);
        $this->assertSame('/groups', $descriptor['endpoint']);
    }

    #[Test]
    public function admin_gets_capabilities_sorts_and_no_pagination()
    {
        $descriptor = $this->describe(1);

        $this->assertSame(['list' => true, 'create' => true, 'delete' => true], $descriptor['capabilities']);
        $this->assertSame(['nameSingular', 'namePlural', 'isHidden'], $descriptor['sorts']);
        // The list is not paginated: every group comes in one response.
        $this->assertArrayNotHasKey('pagination', $descriptor);
        $this->assertArrayNotHasKey('search', $descriptor);
    }

    #[Test]
    public function admin_gets_the_names_as_columns_required_on_create()
    {
        $fields = $this->fieldsByName($this->describe(1));

        foreach (['nameSingular' => 90, 'namePlural' => 85] as $name => $column) {
            $this->assertSame('string', $fields[$name]['kind']);
            // Required to create; an update may leave the names out (but not empty them).
            $this->assertSame(['create' => true], $fields[$name]['required'], $name);
            $this->assertSame(['create' => true, 'update' => true], $fields[$name]['writable'], $name);
            $this->assertTrue($fields[$name]['sortable']);
            $this->assertSame($column, $fields[$name]['ui']['column']);
        }

        $this->assertSame('Name (singular)', $fields['nameSingular']['label']);
        $this->assertSame('Name (plural)', $fields['namePlural']['label']);
        // A placeholder that is a translation key is translated.
        $this->assertSame('Singular (e.g. Mod)', $fields['nameSingular']['ui']['placeholder']);
        $this->assertSame('Plural (e.g. Mods)', $fields['namePlural']['ui']['placeholder']);
    }

    #[Test]
    public function admin_gets_the_colour_and_icon_widgets()
    {
        $fields = $this->fieldsByName($this->describe(1));

        $this->assertSame(['widget' => 'color', 'placeholder' => '#aaaaaa'], $fields['color']['ui']);
        $this->assertTrue($fields['color']['nullable']);
        $this->assertSame(['create' => true, 'update' => true], $fields['color']['writable']);
        $this->assertArrayNotHasKey('sortable', $fields['color']);
        $this->assertSame('Color', $fields['color']['label']);

        $this->assertSame(['widget' => 'icon', 'placeholder' => 'fas fa-bolt'], $fields['icon']['ui']);
        $this->assertTrue($fields['icon']['nullable']);
        $this->assertSame('Icon', $fields['icon']['label']);

        $this->assertSame('boolean', $fields['isHidden']['kind']);
        $this->assertTrue($fields['isHidden']['sortable']);
        $this->assertSame(60, $fields['isHidden']['ui']['column']);
        $this->assertSame('Hide on forum', $fields['isHidden']['label']);

        // The position is set by dragging on the permissions page, not by a form.
        $this->assertArrayNotHasKey('writable', $fields['position']);
        $this->assertArrayNotHasKey('ui', $fields['position']);

        $this->assertArrayNotHasKey('writable', $fields['canEdit']);
        $this->assertArrayNotHasKey('writable', $fields['canDelete']);
    }

    #[Test]
    public function admin_gets_the_hidden_filter()
    {
        $descriptor = $this->describe(1);

        $this->assertSame([['key' => 'hidden', 'kind' => 'boolean']], $descriptor['filters']);
    }

    #[Test]
    public function member_can_describe_but_not_create_or_write()
    {
        $descriptor = $this->describe(2);

        $this->assertSame(['list' => true, 'create' => false], $descriptor['capabilities']);

        foreach ($descriptor['fields'] as $field) {
            $this->assertArrayNotHasKey('writable', $field, $field['name']);
            $this->assertArrayNotHasKey('required', $field, $field['name']);
        }
    }

    #[Test]
    public function the_hidden_filter_describes_itself()
    {
        $this->app();

        $this->assertSame(['kind' => 'boolean'], (new HiddenFilter())->describe(User::find(2)));
    }

    #[Test]
    public function the_list_is_unpaginated_and_says_what_each_actor_may_do()
    {
        $this->prepareDatabase([
            Group::class => array_map(
                fn (int $id) => ['id' => $id, 'name_singular' => "Group $id", 'name_plural' => "Groups $id"],
                range(101, 125)
            ),
        ]);

        $response = $this->send(
            $this->request('GET', '/api/groups', ['authenticatedAs' => 1])
        );

        $body = json_decode($contents = (string) $response->getBody(), true);

        $this->assertEquals(200, $response->getStatusCode(), $contents);
        // 4 installed groups, 100 and 101–125: more than a page of 20.
        $this->assertCount(30, $body['data']);

        $byId = array_column($body['data'], 'attributes', 'id');

        $this->assertTrue($byId['100']['canEdit']);
        $this->assertTrue($byId['100']['canDelete']);
        $this->assertTrue($byId['4']['canDelete']);

        foreach (['1', '2', '3'] as $id) {
            $this->assertTrue($byId[$id]['canEdit'], "group $id");
            $this->assertFalse($byId[$id]['canDelete'], "group $id");
        }
    }

    #[Test]
    public function member_may_neither_edit_nor_delete_groups()
    {
        $response = $this->send(
            $this->request('GET', '/api/groups', ['authenticatedAs' => 2])
        );

        $body = json_decode($contents = (string) $response->getBody(), true);

        $this->assertEquals(200, $response->getStatusCode(), $contents);

        foreach ($body['data'] as $group) {
            $this->assertFalse($group['attributes']['canEdit']);
            $this->assertFalse($group['attributes']['canDelete']);
        }

        $this->assertContains('100', Arr::pluck($body['data'], 'id'));
    }
}
