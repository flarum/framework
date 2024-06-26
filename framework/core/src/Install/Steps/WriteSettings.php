<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Install\Steps;

use Flarum\Foundation\Application;
use Flarum\Install\Step;
use Flarum\Settings\DatabaseSettingsRepository;
use Illuminate\Database\ConnectionInterface;

class WriteSettings implements Step
{
    public function __construct(
        private readonly ConnectionInterface $database,
        private readonly array $custom
    ) {
    }

    public function getMessage(): string
    {
        return 'Writing default settings';
    }

    public function run(): void
    {
        $repo = new DatabaseSettingsRepository($this->database);

        $repo->set('version', Application::VERSION);

        foreach ($this->getSettings() as $key => $value) {
            $repo->set($key, $value);
        }
    }

    private function getSettings(): array
    {
        return $this->custom + $this->getDefaults();
    }

    private function getDefaults(): array
    {
        return [
            'allow_hide_own_posts' => 'reply',
            'allow_post_editing' => 'reply',
            'allow_renaming' => '10',
            'allow_sign_up' => '1',
            'custom_less' => '',
            'default_locale' => 'en',
            'default_route' => '/all',
            'display_name_driver' => 'username',
            'extensions_enabled' => '[]',
            'forum_title' => 'A new Flarum forum',
            'forum_description' => '',
            'mail_driver' => 'mail',
            'mail_format' => 'multipart',
            'mail_from' => 'noreply@localhost',
            'slug_driver_Flarum\User\User' => 'default',
            'theme_colored_header' => '0',
            'color_scheme' => 'auto',
            'theme_primary_color' => '#4D698E',
            'theme_secondary_color' => '#4D698E',
            'welcome_message' => 'Enjoy your new forum! Hop over to discuss.flarum.org if you have any questions, or to join our community!',
            'welcome_title' => 'Welcome to Flarum',
        ];
    }
}
