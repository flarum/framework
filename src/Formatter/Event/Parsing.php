<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Formatter\Event;

use s9e\TextFormatter\Parser;

/**
 * @deprecated beta 15, removed beta 16. Use the Formatter extender instead.
 */
class Parsing
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
     * @var string
     */
    public $text;

    /**
     * @param Parser $parser
     * @param mixed $context
     * @param string $text
     */
    public function __construct(Parser $parser, $context, &$text)
    {
        $this->parser = $parser;
        $this->context = $context;
        $this->text = &$text;
    }
}
