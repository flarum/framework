<?php 
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Api\Serializers;

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
        $attributes = [
            'title' => Core::config('forum_title'),
            'baseUrl' => Core::config('base_url'),
            'basePath' => parse_url(Core::config('base_url'), PHP_URL_PATH) ?: '',
            'debug' => Core::inDebugMode(),
            'apiUrl' => Core::config('api_url'),
            'welcomeTitle' => Core::config('welcome_title'),
            'welcomeMessage' => Core::config('welcome_message'),
            'themePrimaryColor' => Core::config('theme_primary_color'),
            'canView' => $forum->can($this->actor, 'view'),
            'canStartDiscussion' => $forum->can($this->actor, 'startDiscussion'),
            'allowSignUp' => (bool) Core::config('allow_sign_up'),
            'defaultRoute' => Core::config('default_route')
        ];

        if ($this->actor->isAdmin()) {
            $attributes['adminUrl'] = Core::config('admin_url');
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
