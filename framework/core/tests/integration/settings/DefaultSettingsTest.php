<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\settings;

use Flarum\Discussion\Discussion;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Attributes\Test;

/**
 * Tests that settings which power admin dropdowns are registered in
 * flarum.settings.default, so they appear in SettingsRepositoryInterface::all()
 * and reach the admin frontend payload on any install — including upgrades
 * where WriteSettings never ran for newer settings.
 *
 * @see https://github.com/flarum/framework/issues/4488
 */
class DefaultSettingsTest extends TestCase
{
    private function defaults(): Collection
    {
        return $this->app()->getContainer()->make('flarum.settings.default');
    }

    #[Test]
    public function maintenance_mode_has_a_registered_default(): void
    {
        $this->assertTrue($this->defaults()->has('maintenance_mode'));
        $this->assertSame('none', $this->defaults()->get('maintenance_mode'));
    }

    #[Test]
    public function slug_driver_for_discussion_has_a_registered_default(): void
    {
        $this->assertTrue($this->defaults()->has('slug_driver_'.Discussion::class));
        $this->assertSame('default', $this->defaults()->get('slug_driver_'.Discussion::class));
    }

    #[Test]
    public function slug_driver_for_user_has_a_registered_default(): void
    {
        $this->assertTrue($this->defaults()->has('slug_driver_'.User::class));
        $this->assertSame('default', $this->defaults()->get('slug_driver_'.User::class));
    }

    #[Test]
    public function fontawesome_source_has_a_registered_default(): void
    {
        $this->assertTrue($this->defaults()->has('fontawesome_source'));
        $this->assertSame('local', $this->defaults()->get('fontawesome_source'));
    }
}
