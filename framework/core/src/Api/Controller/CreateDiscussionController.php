<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Api\Controller;

use Flarum\Api\Serializer\DiscussionSerializer;
use Flarum\Discussion\Command\ReadDiscussion;
use Flarum\Discussion\Command\StartDiscussion;
use Flarum\Discussion\Discussion;
use Flarum\Http\RequestUtil;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Tobscure\JsonApi\Document;

class CreateDiscussionController extends AbstractCreateController
{
    public ?string $serializer = DiscussionSerializer::class;

    public array $include = [
        'posts',
        'user',
        'lastPostedUser',
        'firstPost',
        'lastPost'
    ];

    public function __construct(
        protected Dispatcher $bus
    ) {
    }

    protected function data(Request $request, Document $document): Discussion
    {
        $actor = RequestUtil::getActor($request);
        $ipAddress = $request->ip();

        $discussion = $this->bus->dispatch(
            new StartDiscussion($actor, $request->json('data', []), $ipAddress)
        );

        // After creating the discussion, we assume that the user has seen all
        // the posts in the discussion; thus, we will mark the discussion
        // as read if they are logged in.
        if ($actor->exists) {
            $this->bus->dispatch(
                new ReadDiscussion($discussion->id, $actor, 1)
            );
        }

        $this->loadRelations(new Collection([$discussion]), $this->extractInclude($request), $request);

        return $discussion;
    }
}
