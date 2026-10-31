<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\ExtensionManager\Tests\unit;

use Flarum\Locale\PrefixedYamlFileLoader;
use Flarum\Locale\Translator;
use Flarum\Locale\TranslatorInterface;
use Symfony\Component\Translation\MessageCatalogueInterface;

/**
 * Flarum's translator, loaded with this extension's English file the way
 * LocaleManager loads it, for tests that build the classes by hand.
 */
class EnglishTranslator
{
    protected static ?TranslatorInterface $translator = null;

    public static function make(): TranslatorInterface
    {
        if (self::$translator === null) {
            $translator = new Translator('en');
            $translator->addLoader('prefixed_yaml', new PrefixedYamlFileLoader());
            $translator->addResource(
                'prefixed_yaml',
                ['file' => dirname(__DIR__, 2).'/locale/en.yml', 'prefix' => ''],
                'en',
                'messages'.MessageCatalogueInterface::INTL_DOMAIN_SUFFIX
            );

            self::$translator = $translator;
        }

        return self::$translator;
    }
}
