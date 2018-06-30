<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Forum;

use Flarum\Foundation\ValidationException;
use Flarum\Frontend\CompilerFactory;
use Flarum\Frontend\RecompileFrontendAssets as BaseListener;
use Flarum\Locale\LocaleManager;
use Flarum\Settings\Event\Saved;
use Flarum\Settings\Event\Saving;
use Flarum\Settings\OverrideSettingsRepository;
use Flarum\Settings\SettingsRepositoryInterface;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Filesystem\FilesystemAdapter;
use League\Flysystem\Adapter\NullAdapter;
use League\Flysystem\Filesystem;
use Less_Exception_Parser;

class RecompileFrontendAssets extends BaseListener
{
    /**
     * @var Container
     */
    protected $container;

    /**
     * @param CompilerFactory $assets
     * @param LocaleManager $locales
     * @param Container $container
     */
    public function __construct(CompilerFactory $assets, LocaleManager $locales, Container $container)
    {
        parent::__construct($assets, $locales);

        $this->container = $container;
    }

    /**
     * @param Dispatcher $events
     */
    public function subscribe(Dispatcher $events)
    {
        parent::subscribe($events);

        $events->listen(Saving::class, [$this, 'whenSettingsSaving']);
    }

    /**
     * @param Saving $event
     * @throws ValidationException
     */
    public function whenSettingsSaving(Saving $event)
    {
        if (isset($event->settings['custom_less'])) {
            // We haven't saved the settings yet, but we want to trial a full
            // recompile of the CSS to see if this custom LESS will break
            // anything. In order to do that, we will temporarily override the
            // settings repository with the new settings so that the recompile
            // is effective. We will also use a dummy filesystem so that nothing
            // is actually written yet.

            $settings = $this->container->make(SettingsRepositoryInterface::class);

            $this->container->extend(
                SettingsRepositoryInterface::class,
                function ($settings) use ($event) {
                    return new OverrideSettingsRepository($settings, $event->settings);
                }
            );

            $assetsDir = $this->assets->getAssetsDir();
            $this->assets->setAssetsDir(new FilesystemAdapter(new Filesystem(new NullAdapter)));

            try {
                $this->assets->makeCss()->commit();

                foreach ($this->locales->getLocales() as $locale => $name) {
                    $this->assets->makeLocaleCss($locale)->commit();
                }
            } catch (Less_Exception_Parser $e) {
                throw new ValidationException(['custom_less' => $e->getMessage()]);
            }

            $this->assets->setAssetsDir($assetsDir);
            $this->container->instance(SettingsRepositoryInterface::class, $settings);
        }
    }

    /**
     * @param Saved $event
     */
    public function whenSettingsSaved(Saved $event)
    {
        parent::whenSettingsSaved($event);

        if (isset($event->settings['custom_less'])) {
            $this->flushCss();
        }
    }
}
