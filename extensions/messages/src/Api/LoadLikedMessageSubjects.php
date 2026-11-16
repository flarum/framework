<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Messages\Api;

use Flarum\Api\Context;
use Flarum\Api\Resource\AbstractDatabaseResource;
use Flarum\Database\Eloquent\Collection;
use Flarum\Extension\ExtensionManager;
use Flarum\Messages\DialogMessage;
use Flarum\Notification\Notification;
use Illuminate\Database\Eloquent\Relations\Relation;

/**
 * Notifications about messages, with flarum/likes enabled, include the message.
 * Whether it may be liked reads its conversation's members and their groups,
 * and rendering it looks up whom it mentions. Loaded for every message at
 * once, rather than one by one.
 *
 * A relation loaded here is serialised as it is, so each is loaded as it
 * would be without this: the conversation scoped to what the actor may see,
 * its members and their groups in full (UserResource leaves out the hidden
 * groups), and mentions as flarum/mentions renders them.
 *
 * Not through eagerLoadWhere('subject'), which would replace core's own
 * loading of the other subjects' relations.
 */
class LoadLikedMessageSubjects
{
    public function __construct(
        protected ExtensionManager $extensions
    ) {
    }

    public function __invoke(Context $context, mixed $data): void
    {
        $messages = [];

        // A page of notifications from Index, a single one from Show.
        foreach (is_array($data) ? $data['models'] : [$data] as $notification) {
            if ($notification instanceof Notification && $notification->subject instanceof DialogMessage) {
                $messages[] = $notification->subject;
            }
        }

        if (! $messages) {
            return;
        }

        $relations = [
            'dialog' => $this->scopedBy('dialogs', $context),
            'dialog.users',
            'dialog.users.groups',
        ];

        if ($this->extensions->isEnabled('flarum-mentions')) {
            // The queries FormatUserMentions and the others make for one message.
            $relations[] = 'mentionsUsers';
            $relations[] = 'mentionsGroups';
            $relations['mentionsPosts'] = fn (Relation $query) => $query->whereVisibleTo($context->getActor())->with(['user', 'discussion']);

            if ($this->extensions->isEnabled('flarum-tags')) {
                $relations[] = 'mentionsTags';
            }
        }

        (new Collection($messages))->loadMissing($relations);
    }

    protected function scopedBy(string $type, Context $context): callable
    {
        $resource = $context->resource($type);

        return function (Relation $query) use ($resource, $context) {
            if ($resource instanceof AbstractDatabaseResource) {
                $resource->scope($query->getQuery(), $context);
            }
        };
    }
}
