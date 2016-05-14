<?php
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Event;

use Illuminate\Support\Collection;

class ExtensionsWereLoaded
{
    /**
     * @var Collection
     */
    protected $extensions;

    /**
     * @param Collection $extensions
     */
    public function __construct(Collection $extensions)
    {
        $this->extensions = $extensions;
    }
}
