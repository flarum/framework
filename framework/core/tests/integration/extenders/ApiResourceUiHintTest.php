<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\extenders;

use Flarum\Api\JsonApi;
use Flarum\Api\Resource\UserResource;
use Flarum\Api\Schema\Attribute;
use Flarum\Extend;
use Flarum\Testing\integration\TestCase;
use PHPUnit\Framework\Attributes\Test;

class ApiResourceUiHintTest extends TestCase
{
    private function usernameField(): Attribute
    {
        /** @var JsonApi $api */
        $api = $this->app()->getContainer()->make(JsonApi::class);

        foreach ($api->getResource('users')->resolveFields() as $field) {
            if ($field->name === 'username') {
                return $field;
            }
        }

        $this->fail('The username field was not found on the users resource.');
    }

    #[Test]
    public function ui_hints_merge_across_extenders()
    {
        $this->extend(
            (new Extend\ApiResource(UserResource::class))
                ->field('username', fn (Attribute $field) => $field->ui(['column' => 90]))
                ->field('username', fn (Attribute $field) => $field->ui(['label' => 'x']))
        );

        $this->assertSame(['column' => 90, 'label' => 'x'], $this->usernameField()->getUi());
    }

    #[Test]
    public function ui_does_not_clobber_schema()
    {
        $field = Attribute::make('foo')->schema(['foo' => 1])->ui(['column' => 1]);

        $this->assertSame(1, $field->getSchema()['foo']);
        $this->assertSame(['column' => 1], $field->getUi());
    }

    #[Test]
    public function schema_after_ui_keeps_the_hints()
    {
        $field = Attribute::make('foo')->ui(['column' => 1])->schema(['foo' => 1]);

        $this->assertSame(['column' => 1], $field->getUi());
        $this->assertArrayNotHasKey('x-flarum-ui', $field->getSchema());
    }

    #[Test]
    public function a_field_without_hints_has_an_empty_ui()
    {
        $this->assertSame([], Attribute::make('foo')->getUi());
    }
}
