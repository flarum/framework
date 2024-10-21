<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Messages\Listener;

use Flarum\Extension\ExtensionManager;
use Flarum\Messages\DialogMessage;
use s9e\TextFormatter\Utils;

class UpdateMentionsMetadataWhenVisible
{
    public function __construct(
        protected ExtensionManager $extensions
    ) {
    }

    public function handle(DialogMessage\Event\Created|DialogMessage\Event\Updated $event): void
    {
        if (! $event->message instanceof DialogMessage) {
            return;
        }

        $content = $event->message->parsed_content;

        $this->syncUserMentions(
            $event->message,
            Utils::getAttributeValues($content, 'USERMENTION', 'id')
        );

        $this->syncPostMentions(
            $event->message,
            Utils::getAttributeValues($content, 'POSTMENTION', 'id')
        );

        $this->syncGroupMentions(
            $event->message,
            Utils::getAttributeValues($content, 'GROUPMENTION', 'id')
        );

        if ($this->extensions->isEnabled('flarum-tags')) {
            $this->syncTagMentions(
                $event->message,
                Utils::getAttributeValues($content, 'TAGMENTION', 'id')
            );
        }
    }

    protected function syncUserMentions(DialogMessage $message, array $mentioned): void
    {
        $message->mentionsUsers()->sync($mentioned);
        $message->unsetRelation('mentionsUsers');
    }

    protected function syncPostMentions(DialogMessage $message, array $mentioned): void
    {
        $message->mentionsPosts()->sync($mentioned);
        $message->unsetRelation('mentionsPosts');
    }

    protected function syncGroupMentions(DialogMessage $message, array $mentioned): void
    {
        $message->mentionsGroups()->sync($mentioned);
        $message->unsetRelation('mentionsGroups');
    }

    protected function syncTagMentions(DialogMessage $message, array $mentioned): void
    {
        $message->mentionsTags()->sync($mentioned);
        $message->unsetRelation('mentionsTags');
    }
}
