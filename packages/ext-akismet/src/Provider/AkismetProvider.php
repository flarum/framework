<?php

namespace Flarum\Akismet\Provider;

use Flarum\Foundation\AbstractServiceProvider;
use Flarum\Http\UrlGenerator;
use Flarum\Settings\SettingsRepositoryInterface;
use TijsVerkoyen\Akismet\Akismet;

class AkismetProvider extends AbstractServiceProvider
{
    public function register()
    {
        $this->app->bind(Akismet::class, function () {
            /** @var SettingsRepositoryInterface $settings */
            $settings = $this->app->make(SettingsRepositoryInterface::class);
            /** @var UrlGenerator $url */
            $url = $this->app->make(UrlGenerator::class);

            return new Akismet(
                $settings->get('flarum-akismet.api_key'),
                $url->to('forum')->base()
            );
        });
    }
}
