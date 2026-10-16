<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\ExtensionManager;

use Flarum\Locale\TranslatorInterface;
use Illuminate\Container\Container;
use Symfony\Component\Yaml\Yaml;
use Throwable;

/**
 * An Extension Manager message from the server, through Flarum's translator.
 *
 * Static, because most of what speaks to the admin is built with `new` deep
 * inside an update (the applier, the journal, the fetcher), and threading a
 * translator through every constructor would change half the codebase for a
 * string. Inside Flarum it is Flarum's translator, so a language pack or FoF
 * Linguist covers these too.
 *
 * 🚨 It never throws. These messages are mostly the explanation of something
 * that already went wrong, sometimes mid-way through replacing Flarum's own
 * files — a translator that failed then would bury the real failure under its
 * own. Outside Flarum (the unit tests, a half-booted request) it reads the
 * English file directly, and at worst it returns the key.
 */
final class Lang
{
    private const PREFIX = 'flarum-extension-manager.api.';

    /** @var array<string, string>|null */
    private static ?array $english = null;

    /**
     * @param array<string, scalar|null> $params
     */
    public static function trans(string $key, array $params = []): string
    {
        $id = self::PREFIX.$key;

        try {
            $container = Container::getInstance();

            if ($container->bound(TranslatorInterface::class)) {
                $text = $container->make(TranslatorInterface::class)->trans($id, $params);

                if (is_string($text) && $text !== $id) {
                    return $text;
                }
            }
        } catch (Throwable) {
            // Fall through to the English file.
        }

        $message = self::english()[$id] ?? null;

        if ($message === null) {
            return $id;
        }

        if ($params === []) {
            return $message;
        }

        if (class_exists(\MessageFormatter::class)) {
            $formatted = \MessageFormatter::formatMessage('en', $message, $params);

            if (is_string($formatted)) {
                return $formatted;
            }
        }

        $pairs = [];
        foreach ($params as $name => $value) {
            $pairs['{'.$name.'}'] = (string) $value;
        }

        return strtr($message, $pairs);
    }

    /**
     * @return array<string, string>
     */
    private static function english(): array
    {
        if (self::$english !== null) {
            return self::$english;
        }

        try {
            $tree = Yaml::parseFile(__DIR__.'/../locale/en.yml');
        } catch (Throwable) {
            $tree = [];
        }

        return self::$english = self::flatten(is_array($tree) ? $tree : []);
    }

    /**
     * @param array<mixed> $tree
     * @return array<string, string>
     */
    private static function flatten(array $tree, string $prefix = ''): array
    {
        $flat = [];

        foreach ($tree as $key => $value) {
            if (is_array($value)) {
                $flat += self::flatten($value, $prefix.$key.'.');
            } elseif (is_scalar($value)) {
                $flat[$prefix.$key] = (string) $value;
            }
        }

        return $flat;
    }
}
