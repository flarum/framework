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

use s9e\TextFormatter\Renderer;

class ConfigureFormatterRenderer
{
    /**
     * @var Renderer
     */
    public $renderer;

    /**
     * @var mixed
     */
    public $context;

    /**
     * @param Renderer $renderer
     * @param mixed $context
     */
    public function __construct(Renderer $renderer, $context)
    {
        $this->renderer = $renderer;
        $this->context = $context;
    }
}
