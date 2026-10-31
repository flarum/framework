<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\OAuthProvider\ClientRegistration;

use Flarum\Settings\SettingsRepositoryInterface;

class DynamicRegistration
{
    public const string SETTING = 'flarum-oauth-provider.allow_dynamic_registration';

    public static function enabled(SettingsRepositoryInterface $settings): bool
    {
        return filter_var($settings->get(self::SETTING, true), FILTER_VALIDATE_BOOLEAN);
    }
}
