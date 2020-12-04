<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\extenders;

use Flarum\Extend;
use Flarum\Tests\integration\RetrievesAuthorizedUsers;
use Flarum\Tests\integration\TestCase;

class SettingsTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function prepDb()
    {
        $this->prepareDatabase([
            'users' => [
                $this->adminUser(),
                $this->normalUser()
            ],
            'settings' => [
                ['key' => 'custom-prefix.custom_setting', 'value' => 'customValue'],
                ['key' => 'custom-prefix.custom_setting2', 'value' => 'customValue']
            ]
        ]);
    }

    /**
     * @test
     */
    public function custom_setting_isnt_serialized_by_default()
    {
        $this->prepDb();

        $response = $this->send(
            $this->request('GET', '/api', [
                'authenticatedAs' => 1,
            ])
        );

        $payload = json_decode($response->getBody(), true);

        $this->assertArrayNotHasKey('customPrefix.customSetting', $payload['data']['attributes']);
    }

    /**
     * @test
     */
    public function custom_setting_serialized_if_added()
    {
        $this->extend(
            (new Extend\Settings())
                ->serializeToForum('customPrefix.customSetting', 'custom-prefix.custom_setting')
        );

        $this->prepDb();

        $response = $this->send(
            $this->request('GET', '/api', [
                'authenticatedAs' => 1,
            ])
        );

        $payload = json_decode($response->getBody(), true);

        $this->assertArrayHasKey('customPrefix.customSetting', $payload['data']['attributes']);
        $this->assertEquals('customValue', $payload['data']['attributes']['customPrefix.customSetting']);
    }

    /**
     * @test
     */
    public function custom_setting_falls_back_to_default_value()
    {
        $this->extend(
            (new Extend\Settings())
                ->serializeToForum('customPrefix.unavailableCustomSetting', 'custom-prefix.unavailable_custom_setting')
                ->default('custom-prefix.unavailable_custom_setting', 'default')
        );

        $this->prepDb();

        $response = $this->send(
            $this->request('GET', '/api', [
                'authenticatedAs' => 1,
            ])
        );

        $payload = json_decode($response->getBody(), true);

        $this->assertArrayHasKey('customPrefix.unavailableCustomSetting', $payload['data']['attributes']);
        $this->assertEquals('default', $payload['data']['attributes']['customPrefix.unavailableCustomSetting']);
    }

    /**
     * @test
     */
    public function custom_setting_callback_works_if_added()
    {
        $this->extend(
            (new Extend\Settings())
                ->serializeToForum('customPrefix.customSetting', 'custom-prefix.custom_setting', function ($value) {
                    return $value.'Modified';
                })
        );

        $this->prepDb();

        $response = $this->send(
            $this->request('GET', '/api', [
                'authenticatedAs' => 1,
            ])
        );

        $payload = json_decode($response->getBody(), true);

        $this->assertArrayHasKey('customPrefix.customSetting', $payload['data']['attributes']);
        $this->assertEquals('customValueModified', $payload['data']['attributes']['customPrefix.customSetting']);
    }

    /**
     * @test
     */
    public function custom_setting_callback_works_with_invokable_class()
    {
        $this->extend(
            (new Extend\Settings())
                ->serializeToForum('customPrefix.customSetting2', 'custom-prefix.custom_setting2', CustomInvokableClass::class)
        );

        $this->prepDb();

        $response = $this->send(
            $this->request('GET', '/api', [
                'authenticatedAs' => 1,
            ])
        );

        $payload = json_decode($response->getBody(), true);

        $this->assertArrayHasKey('customPrefix.customSetting2', $payload['data']['attributes']);
        $this->assertEquals('customValueModifiedByInvokable', $payload['data']['attributes']['customPrefix.customSetting2']);
    }

    /**
     * @test
     */
    public function custom_setting_callback_works_on_default_value()
    {
        $this->extend(
            (new Extend\Settings())
                ->serializeToForum('customPrefix.unavailableCustomSetting2', 'custom-prefix.unavailable_custom_setting2', function ($value) {
                    return $value.'Modified';
                })
                ->default('custom-prefix.unavailable_custom_setting2', 'default')
        );

        $this->prepDb();

        $response = $this->send(
            $this->request('GET', '/api', [
                'authenticatedAs' => 1,
            ])
        );

        $payload = json_decode($response->getBody(), true);

        $this->assertArrayHasKey('customPrefix.unavailableCustomSetting2', $payload['data']['attributes']);
        $this->assertEquals('defaultModified', $payload['data']['attributes']['customPrefix.unavailableCustomSetting2']);
    }
}

class CustomInvokableClass
{
    public function __invoke($value)
    {
        return $value.'ModifiedByInvokable';
    }
}
