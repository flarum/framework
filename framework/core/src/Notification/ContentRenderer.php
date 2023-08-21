<?php

namespace Flarum\Notification;

use s9e\TextFormatter\Bundles\Fatdown;

class ContentRenderer
{
    public function render(string $content): string
    {
        return Fatdown::render(Fatdown::parse($content));
    }
}
