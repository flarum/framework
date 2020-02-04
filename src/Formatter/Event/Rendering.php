<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Formatter\Event;

use Psr\Http\Message\ServerRequestInterface;
use s9e\TextFormatter\Renderer;

class Rendering
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
     * @var string
     */
    public $xml;

    /**
     * @var ServerRequestInterface
     */
    public $request;

    /**
     * @param Renderer $renderer
     * @param mixed $context
     * @param string $xml
     * @param ServerRequestInterface|null $request
     */
    public function __construct(Renderer $renderer, $context, &$xml, ServerRequestInterface $request = null)
    {
        $this->renderer = $renderer;
        $this->context = $context;
        $this->xml = &$xml;
        $this->request = $request;
    }
}
