<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Audit\Integration;

use Flarum\Audit\AuditLogger;
use Flarum\Settings\Event;
use Flarum\Settings\SettingsRepositoryInterface;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Arr;

/**
 * Core setting change logging.
 *
 * Stateful: captures all settings before a save so it can diff them afterwards. Wired
 * through the audit extender's `using()` escape hatch.
 */
class CoreSettingIntegration
{
    /**
     * @var string[]
     */
    public static array $actions = ['setting_changed'];

    /**
     * Only these settings have their old/new values stored. Anything else is logged as
     * a bare "setting_changed" to avoid persisting sensitive values (e.g. secrets).
     *
     * @var string[]
     */
    const array LOGGABLE_VALUES_WHITELIST = [
        'allow_post_editing',
        'allow_renaming',
        'allow_sign_up',
        'default_locale',
        'default_route',
        'display_name_driver',
        'flarum-flags.can_flag_own',
        'flarum-flags.guidelines_url',
        'flarum-tags.max_primary_tags',
        'flarum-tags.max_secondary_tags',
        'flarum-tags.min_primary_tags',
        'flarum-tags.min_secondary_tags',
        'forum_title',
        'mail_driver',
        'mail_encryption',
        'mail_from',
        'mail_host',
        'mail_port',
        'mail_username',
        'show_language_selector',
        'slug_driver_Flarum\Discussion\Discussion',
        'slug_driver_Flarum\User\User',
        'theme_colored_header',
        'theme_dark_mode',
        'theme_primary_color',
        'theme_secondary_color',
        'welcome_title',
    ];

    /**
     * @var array<string, string|null>
     */
    protected array $previousValues = [];

    public function __invoke(Container $container): void
    {
        $events = $container->make(Dispatcher::class);

        $events->listen(Event\Saving::class, [$this, 'saving']);
        $events->listen(Event\Saved::class, [$this, 'saved']);
    }

    public function saving(Event\Saving $event): void
    {
        /** @var SettingsRepositoryInterface $settings */
        $settings = resolve(SettingsRepositoryInterface::class);

        $this->previousValues = $settings->all();
    }

    public function saved(Event\Saved $event): void
    {
        foreach ($event->settings as $key => $value) {
            $previous = Arr::get($this->previousValues, $key);

            if ((string) $value !== $previous) {
                $payload = [
                    'key' => $key,
                ];

                if (in_array($key, self::LOGGABLE_VALUES_WHITELIST)) {
                    $payload += [
                        'old_value' => $previous,
                        'new_value' => (string) $value,
                    ];
                }

                AuditLogger::log('setting_changed', $payload);
            }
        }
    }
}
