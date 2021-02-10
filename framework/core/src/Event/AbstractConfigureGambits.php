<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Event;

use Flarum\Search\GambitManager;

/**
 * @deprecated beta 16, removed in beta 17
 */
abstract class AbstractConfigureGambits
{
    /**
     * @var GambitManager
     */
    public $gambits;

    /**
     * @param \Flarum\Search\GambitManager $gambits
     */
    public function __construct(GambitManager $gambits)
    {
        $this->gambits = $gambits;
    }
}
