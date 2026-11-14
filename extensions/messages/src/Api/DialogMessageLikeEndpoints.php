<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Messages\Api;

use Carbon\Carbon;
use Flarum\Api\Context;
use Flarum\Api\Endpoint;
use Flarum\Messages\DialogMessage;
use Flarum\Messages\DialogMessage\Event\Liked;
use Flarum\Messages\DialogMessage\Event\Unliked;
use Illuminate\Contracts\Events\Dispatcher;

/**
 * Liking a message and taking the like back, with flarum/likes enabled.
 */
class DialogMessageLikeEndpoints
{
    public function __construct(
        protected Dispatcher $events
    ) {
    }

    public function __invoke(): array
    {
        return [
            Endpoint\Endpoint::make('like')
                ->route('POST', '/{id}/like')
                ->authenticated()
                ->can('like')
                ->defaultInclude(['likes'])
                ->action(fn (Context $context) => $this->like($context->model, $context)),
            // No ability: whoever can still see the message can take back a
            // like they gave before the forum stopped letting them like.
            Endpoint\Endpoint::make('unlike')
                ->route('DELETE', '/{id}/like')
                ->authenticated()
                ->defaultInclude(['likes'])
                ->action(fn (Context $context) => $this->unlike($context->model, $context)),
        ];
    }

    protected function like(DialogMessage $message, Context $context): DialogMessage
    {
        $actor = $context->getActor();

        // Atomic, so two requests at once leave one like and one event.
        $liked = $message->likes()->newPivotStatement()->insertOrIgnore([
            'dialog_message_id' => $message->id,
            'user_id' => $actor->id,
            'created_at' => Carbon::now(),
        ]);

        if ($liked) {
            $this->events->dispatch(new Liked($message, $actor));
        }

        return $message;
    }

    protected function unlike(DialogMessage $message, Context $context): DialogMessage
    {
        $actor = $context->getActor();

        if ($message->likes()->detach($actor->id)) {
            $this->events->dispatch(new Unliked($message, $actor));
        }

        return $message;
    }
}
