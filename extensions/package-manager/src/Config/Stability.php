<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\ExtensionManager\Config;

use Flarum\ExtensionManager\Lang;
use RuntimeException;

/**
 * How finished a release has to be before Composer will install it.
 *
 * 🚨 Flarum 2 itself is stable since 2.0.0, but many of its extensions are still
 * betas or release candidates, and a site on the default `stable` cannot install
 * those — the error says the package "could not be found in any version", which
 * sounds like the package is missing rather than like a setting is wrong.
 *
 * What each level means, in words, lives in the locale (`level_*_means`).
 */
class Stability
{
    /** Composer's own order, loosest last. */
    public const LEVELS = ['stable', 'RC', 'beta', 'alpha', 'dev'];

    public function __construct(private JsonFile $file)
    {
    }

    /** @return array{minimumStability:string, preferStable:bool} */
    public function current(): array
    {
        $data = $this->file->read();

        return [
            'minimumStability' => (string) ($data['minimum-stability'] ?? 'stable'),
            // Composer's own default is false, but every Flarum install ships it
            // as true, and reporting the effective value beats reporting the spec.
            'preferStable' => (bool) ($data['prefer-stable'] ?? false),
        ];
    }

    public function set(string $level, bool $preferStable): void
    {
        if (! in_array($level, self::LEVELS, true)) {
            throw new RuntimeException(Lang::trans('config.stability_invalid', ['levels' => implode(', ', self::LEVELS)]));
        }

        $data = $this->file->read();
        $data['minimum-stability'] = $level;
        $data['prefer-stable'] = $preferStable;

        $this->file->write($data);
    }
}
