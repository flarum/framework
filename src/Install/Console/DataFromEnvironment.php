<?php
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Install\Console;

class DataFromEnvironment implements ProvidesData
{
    protected $baseUrl;

    public function getDatabaseConfiguration()
    {
        return [
            'driver'   => 'mysql',
            'host'     => $this->get('DATABASE_HOST'),
            'database' => $this->get('DATABASE_NAME'),
            'username' => $this->get('DATABASE_USER'),
            'password' => $this->get('DATABASE_PASSWORD'),
            'prefix'   => $this->get('DATABASE_PREFIX'),
        ];
    }

    public function getBaseUrl()
    {
        return $this->baseUrl = rtrim($this->get('BASE_URL'), '/');
    }

    public function getAdminUser()
    {
        return [
            'username' => $this->get('ADMIN_USERNAME'),
            'password' => $this->get('ADMIN_PASSWORD'),
            'email'    => $this->get('ADMIN_EMAIL'),
        ];
    }

    public function getSettings()
    {
        $title = $this->get('FORUM_TITLE');
        $baseUrl = $this->baseUrl ?: 'http://localhost';

        return [
            'allow_post_editing' => 'reply',
            'allow_renaming' => '10',
            'allow_sign_up' => '1',
            'custom_less' => '',
            'default_locale' => 'en',
            'default_route' => '/all',
            'extensions_enabled' => '[]',
            'forum_title' => $title,
            'forum_description' => '',
            'mail_driver' => 'mail',
            'mail_from' => 'noreply@' . preg_replace('/^www\./i', '', parse_url($baseUrl, PHP_URL_HOST)),
            'theme_colored_header' => '0',
            'theme_dark_mode' => '0',
            'theme_primary_color' => '#4D698E',
            'theme_secondary_color' => '#4D698E',
            'welcome_message' => 'This is beta software and you should not use it in production.',
            'welcome_title' => 'Welcome to ' . $title,
        ];
    }

    protected function get($variable, $default = null)
    {
        return getenv('FLARUM_' . strtoupper($variable)) ?: $default;
    }
}
