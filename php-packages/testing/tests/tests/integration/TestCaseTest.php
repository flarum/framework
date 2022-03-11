<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Testing\Tests\integration;

use Flarum\Extend;
use Flarum\Foundation\Config;
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Builder;

class TestCaseTest extends TestCase
{
    /**
     * @test
     */
    public function admin_user_created_as_part_of_default_state()
    {
        $this->app();

        $this->assertEquals(1, User::query()->count());
    
        $user = User::find(1);

        $this->assertEquals('admin', $user->username);
        $this->assertEquals('admin@machine.local', $user->email);
        $this->assertTrue($user->isAdmin());
    }

    /**
     * @test
     */
    public function can_add_settings_via_method()
    {
        $this->setting('hello', 'world');
        $this->setting('display_name_driver', 'something_other_than_username');

        $settings = $this->app()->getContainer()->make(SettingsRepositoryInterface::class);

        $this->assertEquals('world', $settings->get('hello'));
        $this->assertEquals('something_other_than_username', $settings->get('display_name_driver'));
    }

    /**
     * @test
     */
    public function settings_cleaned_up_from_previous_method()
    {
        $settings = $this->app()->getContainer()->make(SettingsRepositoryInterface::class);

        $this->assertEquals(null, $settings->get('hello'));
        $this->assertEquals(null, $settings->get('display_name_driver'));
    }

    /**
     * @test
     */
    public function can_add_config_via_method()
    {
        $this->config('hello', 'world');
        $this->config('url', 'https://flarum.org');
        $this->config('level1.level2', 'value');

        $config = $this->app()->getContainer()->make(Config::class);

        $this->assertEquals('world', $config['hello']);
        $this->assertEquals('https://flarum.org', $config['url']);
        $this->assertEquals('value', $config['level1']['level2']);
    }

    /**
     * @test
     */
    public function config_cleaned_up_from_previous_method()
    {
        $config = $this->app()->getContainer()->make(Config::class);

        $this->assertEquals(null, $config['hello']);
        $this->assertEquals('http://localhost', $config['url']);
        $this->assertFalse(isset($config['level1']['level2']));
    }

    /**
     * @test
     */
    public function current_extension_not_applied_by_default()
    {
        $response = $this->send(
            $this->request('GET', '/')
        );

        $this->assertStringNotContainsString('notARealSetting', $response->getBody()->getContents());
    }

    /**
     * @test
     */
    public function current_extension_applied_if_specified()
    {
        $this->extension('flarum-testing-tests');

        $response = $this->send(
            $this->request('GET', '/')
        );

        $this->assertStringContainsString('notARealSetting', $response->getBody()->getContents());
    }

    /**
     * @test
     */
    public function current_extension_migrations_applied_if_specified()
    {
        $this->extension('flarum-testing-tests');

        $tableExists = $this->app()->getContainer()->make(Builder::class)->hasTable('testing_table');
        $this->assertTrue($tableExists);
    }

    /**
     * @test
     */
    public function current_extension_considered_enabled_after_boot()
    {
        $this->extension('flarum-testing-tests');

        $enabled = $this->app()->getContainer()->make('flarum.extensions')->isEnabled('flarum-testing-tests');
        $this->assertTrue($enabled);
    }

    /**
     * @test
     */
    public function can_apply_extenders()
    {
        $this->extend(
            (new Extend\Settings)->serializeToForum('notARealSetting', 'not.a.real.setting')
        );

        $response = $this->send(
            $this->request('GET', '/')
        );

        $this->assertStringContainsString('notARealSetting', $response->getBody()->getContents());
    }

    /**
     * @test
     */
    public function can_apply_route_extenders()
    {
        $this->extend(
            (new Extend\Frontend('forum'))->route('/arbitrary', 'arbitrary')
        );

        $response = $this->send(
            $this->request('GET', '/arbitrary')
        );

        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function routes_added_by_current_extension_not_accessible_by_default()
    {
        $response = $this->send(
            $this->request('GET', '/added-by-extension')
        );

        $this->assertEquals(404, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function routes_added_by_current_extension_accessible()
    {
        $this->extension('flarum-testing-tests');
    
        $response = $this->send(
            $this->request('GET', '/added-by-extension')
        );

        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function extension_url_correct()
    {
        $this->extension('flarum-testing-tests');
        $expected = $this->app()->getContainer()->make('filesystem')->disk('flarum-assets')->url('/flarum-testing-tests/');
        // We need to test this since we override it.
        $extensions = $this->app()->getContainer()->make('flarum.extensions');
        $currExtension = $extensions->getExtension('flarum-testing-tests');
        $baseAssetsUrl = $extensions->getAsset($currExtension, '');

        $this->assertEquals($expected, $baseAssetsUrl);
    }
}