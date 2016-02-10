<?php
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Api\Serializer;

use Flarum\Core\Access\Gate;
use Flarum\Foundation\Application;
use Flarum\Settings\SettingsRepositoryInterface;

class ForumSerializer extends AbstractSerializer
{
    /**
     * {@inheritdoc}
     */
    protected $type = 'forums';

    /**
     * @var Gate
     */
    protected $gate;

    /**
     * @var Application
     */
    protected $app;

    /**
     * @var SettingsRepositoryInterface
     */
    protected $settings;

    /**
     * @param Gate $gate
     * @param Application $app
     * @param SettingsRepositoryInterface $settings
     */
    public function __construct(Gate $gate, Application $app, SettingsRepositoryInterface $settings)
    {
        $this->gate = $gate;
        $this->app = $app;
        $this->settings = $settings;
    }

    /**
     * {@inheritdoc}
     */
    public function getId($model)
    {
        return 1;
    }

    /**
     * {@inheritdoc}
     */
    protected function getDefaultAttributes($model)
    {
        $gate = $this->gate->forUser($this->actor);

        $attributes = [
            'title'              => $this->settings->get('forum_title'),
            'description'        => $this->settings->get('forum_description'),
            'baseUrl'            => $url = $this->app->url(),
            'basePath'           => parse_url($url, PHP_URL_PATH) ?: '',
            'debug'              => $this->app->inDebugMode(),
            'apiUrl'             => $this->app->url('api'),
            'welcomeTitle'       => $this->settings->get('welcome_title'),
            'welcomeMessage'     => $this->settings->get('welcome_message'),
            'themePrimaryColor'  => $this->settings->get('theme_primary_color'),
            'allowSignUp'        => (bool) $this->settings->get('allow_sign_up'),
            'defaultRoute'       => $this->settings->get('default_route'),
            'canViewDiscussions' => $gate->allows('viewDiscussions'),
            'canStartDiscussion' => $gate->allows('startDiscussion')
        ];

        if ($gate->allows('administrate')) {
            $attributes['adminUrl'] = $this->app->url('admin');
            $attributes['version'] = $this->app->version();
        }

        return $attributes;
    }

    /**
     * @return \Tobscure\JsonApi\Relationship
     */
    protected function groups($model)
    {
        return $this->hasMany($model, 'Flarum\Api\Serializer\GroupSerializer');
    }
}
