<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\extenders;

use Flarum\Extend;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\Testing\integration\UsesSettings;

class SettingsTest extends TestCase
{
    use RetrievesAuthorizedUsers;
    use UsesSettings;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->prepareDatabase([
            'users' => [
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
        $this->purgeSettingsCache();

        $response = $this->send(
            $this->request('GET', '/api', [
                'authenticatedAs' => 1,
            ])
        );

        $payload = json_decode($response->getBody()->getContents(), true);

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

        $this->purgeSettingsCache();

        $response = $this->send(
            $this->request('GET', '/api', [
                'authenticatedAs' => 1,
            ])
        );

        $payload = json_decode($response->getBody()->getContents(), true);

        $this->assertArrayHasKey('customPrefix.customSetting', $payload['data']['attributes']);
        $this->assertEquals('customValue', $payload['data']['attributes']['customPrefix.customSetting']);
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

        $this->purgeSettingsCache();

        $response = $this->send(
            $this->request('GET', '/api', [
                'authenticatedAs' => 1,
            ])
        );

        $payload = json_decode($response->getBody()->getContents(), true);

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

        $this->purgeSettingsCache();

        $response = $this->send(
            $this->request('GET', '/api', [
                'authenticatedAs' => 1,
            ])
        );

        $payload = json_decode($response->getBody()->getContents(), true);

        $this->assertArrayHasKey('customPrefix.customSetting2', $payload['data']['attributes']);
        $this->assertEquals('customValueModifiedByInvokable', $payload['data']['attributes']['customPrefix.customSetting2']);
    }

    /**
     * @test
     */
    public function custom_setting_falls_back_to_default()
    {
        $this->extend(
            (new Extend\Settings())
                ->serializeToForum('customPrefix.noCustomSetting', 'custom-prefix.no_custom_setting', null, 'customDefault')
        );

        $this->purgeSettingsCache();

        $response = $this->send(
            $this->request('GET', '/api', [
                'authenticatedAs' => 1,
            ])
        );

        $payload = json_decode($response->getBody()->getContents(), true);

        $this->assertArrayHasKey('customPrefix.noCustomSetting', $payload['data']['attributes']);
        $this->assertEquals('customDefault', $payload['data']['attributes']['customPrefix.noCustomSetting']);
    }

    /**
     * @test
     */
    public function custom_setting_default_passed_to_callback()
    {
        $this->extend(
            (new Extend\Settings())
                ->serializeToForum('customPrefix.noCustomSetting', 'custom-prefix.no_custom_setting', function ($value) {
                    return $value.'Modified2';
                }, 'customDefault')
        );

        $this->purgeSettingsCache();

        $response = $this->send(
            $this->request('GET', '/api', [
                'authenticatedAs' => 1,
            ])
        );

        $payload = json_decode($response->getBody()->getContents(), true);

        $this->assertArrayHasKey('customPrefix.noCustomSetting', $payload['data']['attributes']);
        $this->assertEquals('customDefaultModified2', $payload['data']['attributes']['customPrefix.noCustomSetting']);
    }
}

class CustomInvokableClass
{
    public function __invoke($value)
    {
        return $value.'ModifiedByInvokable';
    }
}
