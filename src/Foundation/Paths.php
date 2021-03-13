<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Foundation;

use InvalidArgumentException;

/**
 * @property-read string base
 * @property-read string public
 * @property-read string storage
 * @property-read string vendor
 */
class Paths
{
    private $paths;

    public function __construct(array $paths)
    {
        if (! isset($paths['base'], $paths['public'], $paths['storage'])) {
            throw new InvalidArgumentException(
                'Paths array requires keys base, public and storage'
            );
        }

        $this->paths = array_map(function ($path) {
            return rtrim($path, '\/');
        }, $paths);

        // Assume a standard Composer directory structure unless specified
        $this->paths['vendor'] = $this->vendor ?? $this->base.'/vendor';
    }

    public function __get($name): ?string
    {
        return $this->paths[$name] ?? null;
    }
}
