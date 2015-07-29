<?php namespace Flarum\Api\Serializers;

use Flarum\Core;
use Flarum\Core\Groups\Permission;

class ForumSerializer extends Serializer
{
    /**
     * {@inheritdoc}
     */
    protected $type = 'forums';

    /**
     * {@inheritdoc}
     */
    protected function getId($forum)
    {
        return 1;
    }

    /**
     * {@inheritdoc}
     */
    protected function getDefaultAttributes($forum)
    {
        $attributes = [
            'title' => Core::config('forum_title'),
            'baseUrl' => Core::config('base_url'),
            'apiUrl' => Core::config('api_url'),
            'welcomeTitle' => Core::config('welcome_title'),
            'welcomeMessage' => Core::config('welcome_message'),
            'themePrimaryColor' => Core::config('theme_primary_color')
        ];

        if ($this->actor->isAdmin()) {
            $attributes['config'] = app('Flarum\Core\Settings\SettingsRepository')->all();
            $attributes['availableLocales'] = app('flarum.localeManager')->getLocales();
            $attributes['permissions'] = Permission::map();
        }

        return $attributes;
    }

    /**
     * @return callable
     */
    protected function groups()
    {
        return $this->hasMany('Flarum\Api\Serializers\GroupSerializer');
    }
}
