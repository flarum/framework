<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Akismet\Provider;

use Flarum\Akismet\Akismet;
use Flarum\Extension\ExtensionManager;
use Flarum\Foundation\AbstractServiceProvider;
use Flarum\Foundation\Application;
use Flarum\Foundation\Config;
use Flarum\Http\UrlGenerator;
use Flarum\Settings\SettingsRepositoryInterface;
use Illuminate\Container\Container;

class AkismetProvider extends AbstractServiceProvider
{
    public function register(): void
    {
        $this->container->bind(Akismet::class, function (Container $container) {
            /** @var SettingsRepositoryInterface $settings */
            $settings = $container->make(SettingsRepositoryInterface::class);
            /** @var UrlGenerator $url */
            $url = $container->make(UrlGenerator::class);
            /** @var Config $config */
            $config = $container->make(Config::class);
            /** @var ExtensionManager $extensions */
            $extensions = $this->container->make(ExtensionManager::class);
            /** @var Application $app */
            $app = $container->make(Application::class);

            return new Akismet(
                $settings->get('flarum-akismet.api_key'),
                $url->to('forum')->base(),
                $app::VERSION,
                $extensions->getExtension('flarum-akismet')->getVersion() ?? 'unknown',
                $config->inDebugMode()
            );
        });
    }
}
