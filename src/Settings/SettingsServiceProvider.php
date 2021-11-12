<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Settings;

use Flarum\Foundation\AbstractServiceProvider;
use Flarum\Frontend\RecompileFrontendAssets;
use Flarum\Forum\ValidateCustomLess;
use Flarum\Locale\LocaleManager;
use Flarum\Settings\Event\Saved;
use Flarum\Settings\Event\Saving;
use Flarum\Settings\SettingsValidator;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Collection;

class SettingsServiceProvider extends AbstractServiceProvider
{
    /**
     * {@inheritdoc}
     */
    public function register()
    {
        $this->container->singleton('flarum.settings.default', function () {
            return new Collection([
                'theme_primary_color' => '#4D698E',
                'theme_secondary_color' => '#4D698E',
            ]);
        });

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


    public function boot(Container $container, Dispatcher $events, SettingsValidator $settingsValidator)
    {
        $events->listen(
            Saved::class,
            function (Saved $event) use ($container) {
                $recompile = new RecompileFrontendAssets(
                    $container->make('flarum.assets.forum'),
                    $container->make(LocaleManager::class)
                );
                $recompile->whenSettingsSaved($event);

                $validator = new ValidateCustomLess(
                    $container->make('flarum.assets.forum'),
                    $container->make('flarum.locales'),
                    $container,
                    $container->make('flarum.less.config')
                );
                $validator->whenSettingsSaved($event);
            }
        );

        $events->listen(
            Saving::class,
            function (Saving $event) use ($container, $settingsValidator) {
                $settingsValidator->assertValid($event->settings);

                $validator = new ValidateCustomLess(
                    $container->make('flarum.assets.forum'),
                    $container->make('flarum.locales'),
                    $container,
                    $container->make('flarum.less.config')
                );
                $validator->whenSettingsSaving($event);
            }
        );
    }
}
