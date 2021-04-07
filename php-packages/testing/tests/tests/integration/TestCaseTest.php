<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Testing\Tests\integration;

use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\Testing\integration\TestCase;

class TestCaseTest extends TestCase
{
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
}