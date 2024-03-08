<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Api\Resource;

use Flarum\Api\Context;
use Flarum\Api\Endpoint;
use Flarum\Api\Schema;
use Flarum\Foundation\Application;
use Flarum\Foundation\Config;
use Flarum\Group\Group;
use Flarum\Http\UrlGenerator;
use Flarum\Settings\SettingsRepositoryInterface;
use Illuminate\Contracts\Filesystem\Cloud;
use Illuminate\Contracts\Filesystem\Factory;
use Illuminate\Contracts\Filesystem\Filesystem;
use stdClass;
use Tobyz\JsonApiServer\Resource\Findable;

/**
 * @extends AbstractResource<stdClass>
 */
class ForumResource extends AbstractResource implements Findable
{
    /**
     * @var Filesystem&Cloud
     */
    protected Filesystem $assetsFilesystem;

    public function __construct(
        protected UrlGenerator $url,
        protected SettingsRepositoryInterface $settings,
        protected Config $config,
        Factory $filesystemFactory
    ) {
        $this->assetsFilesystem = $filesystemFactory->disk('flarum-assets');
    }

    public function type(): string
    {
        return 'forums';
    }

    public function getId(object $model, \Tobyz\JsonApiServer\Context $context): string
    {
        return '1';
    }

    public function id(\Tobyz\JsonApiServer\Context $context): ?string
    {
        return '1';
    }

    public function find(string $id, \Tobyz\JsonApiServer\Context $context): ?object
    {
        return new stdClass();
    }

    public function endpoints(): array
    {
        return [
            Endpoint\Show::make()
                ->defaultInclude(['groups', 'actor.groups'])
                ->route('GET', '/'),
        ];
    }

    public function fields(): array
    {
        $forumUrl = $this->url->to('forum')->base();
        $path = parse_url($forumUrl, PHP_URL_PATH) ?: '';

        return [
            Schema\Str::make('title')
                ->get(fn () => $this->settings->get('forum_title')),
            Schema\Str::make('description')
                ->get(fn () => $this->settings->get('forum_description')),
            Schema\Boolean::make('showLanguageSelector')
                ->get(fn () => $this->settings->get('show_language_selector', true)),
            Schema\Str::make('baseUrl')
                ->get(fn () => $forumUrl),
            Schema\Str::make('basePath')
                ->get(fn () => $path),
            Schema\Str::make('baseOrigin')
                ->get(fn () => substr($forumUrl, 0, strlen($forumUrl) - strlen($path))),
            Schema\Str::make('debug')
                ->get(fn () => $this->config->inDebugMode()),
            Schema\Str::make('apiUrl')
                ->get(fn () => $this->url->to('api')->base()),
            Schema\Str::make('welcomeTitle')
                ->get(fn () => $this->settings->get('welcome_title')),
            Schema\Str::make('welcomeMessage')
                ->get(fn () => $this->settings->get('welcome_message')),
            Schema\Str::make('themePrimaryColor')
                ->get(fn () => $this->settings->get('theme_primary_color')),
            Schema\Str::make('themeSecondaryColor')
                ->get(fn () => $this->settings->get('theme_secondary_color')),
            Schema\Str::make('logoUrl')
                ->get(fn () => $this->getLogoUrl()),
            Schema\Str::make('faviconUrl')
                ->get(fn () => $this->getFaviconUrl()),
            Schema\Str::make('headerHtml')
                ->get(fn () => $this->settings->get('custom_header')),
            Schema\Str::make('footerHtml')
                ->get(fn () => $this->settings->get('custom_footer')),
            Schema\Boolean::make('allowSignUp')
                ->get(fn () => $this->settings->get('allow_sign_up')),
            Schema\Str::make('defaultRoute')
                ->get(fn () => $this->settings->get('default_route')),
            Schema\Boolean::make('canViewForum')
                ->get(fn ($model, Context $context) => $context->getActor()->can('viewForum')),
            Schema\Boolean::make('canStartDiscussion')
                ->get(fn ($model, Context $context) => $context->getActor()->can('startDiscussion')),
            Schema\Boolean::make('canSearchUsers')
                ->get(fn ($model, Context $context) => $context->getActor()->can('searchUsers')),
            Schema\Boolean::make('canCreateAccessToken')
                ->get(fn ($model, Context $context) => $context->getActor()->can('createAccessToken')),
            Schema\Boolean::make('moderateAccessTokens')
                ->get(fn ($model, Context $context) => $context->getActor()->can('moderateAccessTokens')),
            Schema\Boolean::make('canEditUserCredentials')
                ->get(fn ($model, Context $context) => $context->getActor()->hasPermission('user.editCredentials')),
            Schema\Str::make('assetsBaseUrl')
                ->get(fn () => rtrim($this->assetsFilesystem->url(''), '/')),
            Schema\Str::make('jsChunksBaseUrl')
                ->get(fn () => $this->assetsFilesystem->url('js')),

            Schema\Str::make('adminUrl')
                ->visible(fn ($model, Context $context) => $context->getActor()->can('administrate'))
                ->get(fn () => $this->url->to('admin')->base()),
            Schema\Str::make('version')
                ->visible(fn ($model, Context $context) => $context->getActor()->can('administrate'))
                ->get(fn () => Application::VERSION),

            Schema\Relationship\ToMany::make('groups')
                ->includable()
                ->get(fn ($model, Context $context) => Group::whereVisibleTo($context->getActor())->get()->all()),
            Schema\Relationship\ToOne::make('actor')
                ->type('users')
                ->includable()
                ->get(fn ($model, Context $context) => $context->getActor()->isGuest() ? null : $context->getActor()),
        ];
    }

    protected function getLogoUrl(): ?string
    {
        $logoPath = $this->settings->get('logo_path');

        return $logoPath ? $this->getAssetUrl($logoPath) : null;
    }

    protected function getFaviconUrl(): ?string
    {
        $faviconPath = $this->settings->get('favicon_path');

        return $faviconPath ? $this->getAssetUrl($faviconPath) : null;
    }

    public function getAssetUrl(string $assetPath): string
    {
        return $this->assetsFilesystem->url($assetPath);
    }
}
