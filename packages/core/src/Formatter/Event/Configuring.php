<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Formatter\Event;

use s9e\TextFormatter\Configurator;

/**
 * @deprecated beta 15, removed beta 16. Use the Formatter extender instead.
 */
class Configuring
{
    /**
     * @var Configurator
     */
    public $configurator;

    /**
     * @param Configurator $configurator
     */
    public function __construct(Configurator $configurator)
    {
        $this->configurator = $configurator;
    }
}
