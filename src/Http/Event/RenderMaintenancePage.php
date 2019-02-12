<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Http\Event;

class RenderMaintenancePage
{
    /**
     * @var string
     */
    public $view;
    /**
     * HTTP status code.
     *
     * @var int
     */
    public $code;

    public function __construct(string $view, int $code = 503)
    {
        $this->view = $view;
        $this->code = $code;
    }
}
