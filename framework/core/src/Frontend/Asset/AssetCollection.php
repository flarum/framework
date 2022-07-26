<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Frontend\Asset;

use Illuminate\Support\Collection;

class AssetCollection extends Collection
{
    protected array $quickMap = [
        'css' => Css::class,
        'js' => Js::class,
    ];

    public function ofType(string $type)
    {
        $type = $this->quickMap[$type] ?? $type;

        return $this->filter(function (Type $asset) use ($type) {
            return $asset instanceof $type;
        });
    }
}
