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
use Flarum\User\User;
use PHPUnit\Framework\Attributes\Test;

class SettingsTest extends TestCase
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
                $this->normalUser()
            ]
        ]);

        $this->setting('custom-prefix.custom_setting', 'customValue');
        $this->setting('custom-prefix.custom_setting2', 'customValue');
    }

    #[Test]
    public function custom_setting_isnt_serialized_by_default()
    {
        $response = $this->send(
            $this->request('GET', '/api', [
                'authenticatedAs' => 1,
            ])
        );

        $payload = json_decode($response->getBody()->getContents(), true);

        $this->assertArrayNotHasKey('customPrefix.customSetting', $payload['data']['attributes']);
    }

    #[Test]
    public function custom_setting_serialized_if_added()
    {
        $this->extend(
            (new Extend\Settings())
                ->serializeToForum('customPrefix.customSetting', 'custom-prefix.custom_setting')
        );

        $response = $this->send(
            $this->request('GET', '/api', [
                'authenticatedAs' => 1,
            ])
        );

        $payload = json_decode($response->getBody()->getContents(), true);

        $this->assertArrayHasKey('customPrefix.customSetting', $payload['data']['attributes']);
        $this->assertEquals('customValue', $payload['data']['attributes']['customPrefix.customSetting']);
    }

    #[Test]
    public function custom_setting_callback_works_if_added()
    {
        $this->extend(
            (new Extend\Settings())
                ->serializeToForum('customPrefix.customSetting', 'custom-prefix.custom_setting', function ($value) {
                    return $value.'Modified';
                })
        );

        $response = $this->send(
            $this->request('GET', '/api', [
                'authenticatedAs' => 1,
            ])
        );

        $payload = json_decode($response->getBody()->getContents(), true);

        $this->assertArrayHasKey('customPrefix.customSetting', $payload['data']['attributes']);
        $this->assertEquals('customValueModified', $payload['data']['attributes']['customPrefix.customSetting']);
    }

    #[Test]
    public function custom_setting_callback_can_cast_to_type()
    {
        $this->extend(
            (new Extend\Settings())
                ->serializeToForum('customPrefix.customSetting', 'custom-prefix.custom_setting', function ($value) {
                    return (bool) $value;
                })
        );

        $response = $this->send(
            $this->request('GET', '/api', [
                'authenticatedAs' => 1,
            ])
        );

        $payload = json_decode($response->getBody()->getContents(), true);

        $this->assertArrayHasKey('customPrefix.customSetting', $payload['data']['attributes']);
        $this->assertEquals(true, $payload['data']['attributes']['customPrefix.customSetting']);
    }

    #[Test]
    public function custom_setting_callback_works_with_invokable_class()
    {
        $this->extend(
            (new Extend\Settings())
                ->serializeToForum('customPrefix.customSetting2', 'custom-prefix.custom_setting2', CustomInvokableClass::class)
        );

        $response = $this->send(
            $this->request('GET', '/api', [
                'authenticatedAs' => 1,
            ])
        );

        $payload = json_decode($response->getBody()->getContents(), true);

        $this->assertArrayHasKey('customPrefix.customSetting2', $payload['data']['attributes']);
        $this->assertEquals('customValueModifiedByInvokable', $payload['data']['attributes']['customPrefix.customSetting2']);
    }

    #[Test]
    public function custom_setting_default_prioritizes_extender()
    {
        $this->extend(
            (new Extend\Settings())
                ->serializeToForum('customPrefix.unavailableCustomSetting3', 'custom-prefix.unavailable_custom_setting3')
                ->default('custom-prefix.unavailable_custom_setting3', 'extenderDefault')
                ->default('custom-prefix.unavailable_custom_setting100', 'extenderDefault100'),
            (new Extend\Settings())
                ->default('custom-prefix.unavailable_custom_setting200', 'extenderDefault200')
        );

        $settings = $this->app()->getContainer()->make('flarum.settings');

        $this->assertEquals('extenderDefault', $settings->get('custom-prefix.unavailable_custom_setting3'));
        $this->assertEquals('extenderDefault100', $settings->get('custom-prefix.unavailable_custom_setting100'));
        $this->assertEquals('extenderDefault200', $settings->get('custom-prefix.unavailable_custom_setting200'));
    }

    #[Test]
    public function custom_setting_default_falls_back_to_parameter()
    {
        $this->extend(
            (new Extend\Settings())
                ->serializeToForum('customPrefix.unavailableCustomSetting4', 'custom-prefix.unavailable_custom_setting4')
        );

        $value = $this->app()
            ->getContainer()
            ->make('flarum.settings')
            ->get('custom-prefix.unavailable_custom_setting4', 'defaultParameterValue');

        $this->assertEquals('defaultParameterValue', $value);
    }

    #[Test]
    public function null_custom_setting_returns_null()
    {
        $this->setting('custom-prefix.custom_null_setting', null);

        $this->extend(
            (new Extend\Settings())
                ->default('custom-prefix.custom_null_setting', 'extenderDefault')
        );

        $value = $this->app()
            ->getContainer()
            ->make('flarum.settings')
            ->get('custom-prefix.custom_null_setting');

        $this->assertEquals(null, $value);
    }

    #[Test]
    public function custom_less_var_does_not_work_by_default()
    {
        $this->extend(
            (new Extend\Frontend('forum'))
                ->css(__DIR__.'/../../fixtures/less/config.less'),
        );

        $response = $this->send($this->request('GET', '/'));

        $this->assertEquals(500, $response->getStatusCode());
    }

    #[Test]
    public function custom_less_var_works_if_registered()
    {
        $this->extend(
            (new Extend\Frontend('forum'))
                ->css(__DIR__.'/../../fixtures/less/config.less'),
            (new Extend\Settings())
                ->registerLessConfigVar('custom-config-setting', 'custom-prefix.custom_setting')
        );

        $response = $this->send($this->request('GET', '/'));

        $cssFilePath = $this->app()->getContainer()->make('filesystem')->disk('flarum-assets')->path('forum.css');
        $this->assertStringContainsString('--custom-config-setting:customValue', file_get_contents($cssFilePath));

        $this->assertEquals(200, $response->getStatusCode());
    }

    #[Test]
    public function cant_save_setting_if_invalid_less_var()
    {
        $this->extend(
            (new Extend\Settings())
                ->registerLessConfigVar('custom-config-setting2', 'custom-prefix.custom_setting2')
        );

        $response = $this->send($this->request('POST', '/api/settings', [
            'authenticatedAs' => 1,
            'json' => [
                'custom-prefix.custom_setting2' => '@muralf'
            ],
        ]));

        $this->assertEquals(422, $response->getStatusCode());
    }
}

class CustomInvokableClass
{
    public function __invoke($value)
    {
        return $value.'ModifiedByInvokable';
    }
}
