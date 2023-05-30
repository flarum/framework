<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Http;

use Flarum\Database\AbstractModel;
use Illuminate\Support\Arr;

class SlugManager
{
    public function __construct(
        protected array $drivers = []
    ) {
    }

    /**
     * @template T of AbstractModel
     * @param class-string<T> $resourceName
     * @return SlugDriverInterface<T>
     */
    public function forResource(string $resourceName): SlugDriverInterface
    {
        return Arr::get($this->drivers, $resourceName);
    }
}
