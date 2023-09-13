<?php

namespace Flarum\Formatter;

/**
 * This is intended as a utility/helper within Flarum's blade templates.
 */
class HtmlRenderer
{
    public function __construct(public Formatter $formatter)
    {
    }
    
    public function render(string $content): string
    {
        return $this->formatter->render($this->formatter->parse($content));
    }
}
