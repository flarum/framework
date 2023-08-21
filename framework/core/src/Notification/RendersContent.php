<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

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
