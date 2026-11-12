<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Settings;

use Flarum\Foundation\AbstractServiceProvider;
use Flarum\Settings\Event\Saving;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Collection;

class SettingsServiceProvider extends AbstractServiceProvider
{
    public function register(): void
    {
        $this->container->singleton('flarum.settings.default', fn () => self::defaults());

        $this->container->singleton(SettingsRepositoryInterface::class, function (Container $container) {
            return new DefaultSettingsRepository(
                new MemoryCacheSettingsRepository(
                    new DatabaseSettingsRepository(
                        $container->make(ConnectionInterface::class)
                    )
                ),
                $container->make('flarum.settings.default')
            );
        });

        $this->container->alias(SettingsRepositoryInterface::class, 'flarum.settings');
    }

    /** @internal */
    public static function defaults(): Collection
    {
        return new Collection([
            'theme_primary_color' => '#4D698E',
            'theme_secondary_color' => '#4D698E',
            'color_scheme' => 'auto',
            'mail_format' => 'multipart',
            'app_icon_background' => '#ffffff',
            'open_as_app' => true,
            'mail_suppress_bounced' => false,
            'fontawesome_source' => 'local',
            'fontawesome_forced_style' => '',
            'maintenance_mode' => 'none',
            'show_language_selector' => true,
            'show_theme_selector' => true,
            'show_post_source' => false,
            'slug_driver_Flarum\Discussion\Discussion' => 'default',
            'slug_driver_Flarum\User\User' => 'default',
            'search_driver_Flarum\User\User' => 'default',
            'search_driver_Flarum\Discussion\Discussion' => 'default',
            'search_driver_Flarum\Group\Group' => 'default',
            'search_driver_Flarum\Post\Post' => 'default',
            'search_driver_Flarum\Http\AccessToken' => 'default',
            'pgsql_search_configuration' => 'english',
            'search_cjk_mode' => false,
        ]);
    }

    public function boot(Dispatcher $events, SettingsValidator $settingsValidator): void
    {
        $events->listen(
            Saving::class,
            function (Saving $event) use ($settingsValidator) {
                $settingsValidator->assertValid($event->settings);
            }
        );
    }
}
