<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Foundation;

interface SiteInterface
{
    /**
     * Create and boot a Flarum application instance.
     */
    public function init(): AppInterface;

    /**
     * Bootstrappers make up the booting process of the Application.
     */
    public function bootstrappers(): array;
}
