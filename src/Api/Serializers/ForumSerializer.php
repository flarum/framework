<?php namespace Flarum\Api\Serializers;

use Flarum\Core;

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
        return [
            'title' => $forum->title,
            'baseUrl' => Core::config('base_url'),
            'apiUrl' => Core::config('api_url'),
            'welcomeTitle' => Core::config('welcome_title'),
            'welcomeMessage' => Core::config('welcome_message'),
            'themePrimaryColor' => Core::config('theme_primary_color')
        ];
    }
}
