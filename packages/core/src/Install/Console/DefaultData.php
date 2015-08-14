<?php namespace Flarum\Install\Console;

class DefaultData implements ProvidesData
{
    public function getDatabaseConfiguration()
    {
        return [
            'driver'    => 'mysql',
            'host'      => 'localhost',
            'database'  => 'flarum_console',
            'username'  => 'root',
            'password'  => 'root',
            'prefix'    => '',
        ];
    }

    public function getAdminUser()
    {
        return [
            'username'              => 'admin',
            'password'              => 'admin',
            'email'                 => 'admin@example.com',
        ];
    }

    public function getSettings()
    {
        return [
            'admin_url' => 'http://flarum.dev/admin',
            'allow_post_editing' => 'reply',
            'allow_renaming' => '10',
            'allow_sign_up' => '1',
            'api_url' => 'http://flarum.dev/api',
            'base_url' => 'http://flarum.dev',
            'custom_less' => '',
            'default_locale' => 'en',
            'default_route' => '/all',
            'extensions_enabled' => '[]',
            'forum_title' => 'Development Forum',
            'forum_description' => '',
            'mail_driver' => 'mail',
            'mail_from' => 'noreply@flarum.dev',
            'theme_colored_header' => '0',
            'theme_dark_mode' => '0',
            'theme_primary_color' => '#29415E',
            'theme_secondary_color' => '#29415E',
            'welcome_message' => 'This is beta software and you should not use it in production.',
            'welcome_title' => 'Welcome to Development Forum',
        ];
    }
}
