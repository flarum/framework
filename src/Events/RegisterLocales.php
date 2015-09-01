<?php
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Events;

use Flarum\Locale\LocaleManager;

class RegisterLocales
{
    /**
     * @var LocaleManager
     */
    public $manager;

    /**
     * @param LocaleManager $manager
     */
    public function __construct(LocaleManager $manager)
    {
        $this->manager = $manager;
    }

    public function addLocale($locale, $name)
    {
        $this->manager->addLocale($locale, $name);
    }

    public function addTranslations($locale, $file)
    {
        $this->manager->addTranslations($locale, $file);
    }

    public function addJsFile($locale, $file)
    {
        $this->manager->addJsFile($locale, $file);
    }

    public function addConfig($locale, $file)
    {
        $this->manager->addConfig($locale, $file);
    }
}
