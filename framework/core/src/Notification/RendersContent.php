<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Notification;

use s9e\TextFormatter\Bundles\Fatdown;

trait RendersContent
{
    public function renderContent(string $content): string
    {
        return Fatdown::render(Fatdown::parse($content));
    }
}
