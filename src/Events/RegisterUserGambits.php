<?php
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Events;

use Flarum\Core\Search\GambitManager;

class RegisterUserGambits
{
    /**
     * @var GambitManager
     */
    protected $gambits;

    /**
     * @param GambitManager $gambits
     */
    public function __construct(GambitManager $gambits)
    {
        $this->gambits = $gambits;
    }

    /**
     * @param string $gambit
     */
    public function register($gambit)
    {
        $this->gambits->add($gambit);
    }
}
