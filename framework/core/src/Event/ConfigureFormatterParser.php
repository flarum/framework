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

use s9e\TextFormatter\Parser;

class ConfigureFormatterParser
{
    /**
     * @var Parser
     */
    public $parser;

    /**
     * @var mixed
     */
    public $context;

    /**
     * @param Parser $parser
     * @param mixed $context
     */
    public function __construct(Parser $parser, $context)
    {
        $this->parser = $parser;
        $this->context = $context;
    }
}
