<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Testing\Tests\integration;

use Flarum\Extend;
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;

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
}