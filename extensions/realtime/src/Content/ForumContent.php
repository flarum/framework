<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Realtime\Content;

use Flarum\Frontend\Document;
use Flarum\Settings\SettingsRepositoryInterface;

class ForumContent
{
    public function __construct(
        protected SettingsRepositoryInterface $settings
    ) {
    }

    public function __invoke(Document $document): void
    {
        $document->payload['flarum-realtime.typing-indicator'] = (bool) $this->settings->get('flarum-realtime.typing-indicator');
        $document->payload['flarum-realtime.release-discussion-updates'] = (bool) $this->settings->get('flarum-realtime.release-discussion-updates');
    }
}
