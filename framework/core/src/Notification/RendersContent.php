<?php

namespace Flarum\Notification;

trait RendersContent
{
    public function __construct(protected ContentRenderer $renderer)
    {
    }

    public function renderContent(string $content): string
    {
        return $this->renderer->render($content);
    }
}
