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
use Flarum\Post\Floodgate;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Support\Arr;
use Psr\Http\Message\ServerRequestInterface;
use Tobscure\JsonApi\Document;

class CreateDiscussionController extends AbstractCreateController
{
    /**
     * {@inheritdoc}
     */
    public $serializer = DiscussionSerializer::class;

    /**
     * {@inheritdoc}
     */
    public $include = [
        'posts',
        'user',
        'lastPostedUser',
        'firstPost',
        'lastPost'
    ];

    /**
     * @var Dispatcher
     */
    protected $bus;

    /**
     * @var Floodgate
     */
    protected $floodgate;

    /**
     * @param Dispatcher $bus
     * @param Floodgate $floodgate
     */
    public function __construct(Dispatcher $bus, Floodgate $floodgate)
    {
        $this->bus = $bus;
        $this->floodgate = $floodgate;
    }

    /**
     * {@inheritdoc}
     */
    protected function data(ServerRequestInterface $request, Document $document)
    {
        $actor = $request->getAttribute('actor');
        $ipAddress = Arr::get($request->getServerParams(), 'REMOTE_ADDR', '127.0.0.1');

        if (! $request->getAttribute('bypassFloodgate')) {
            $this->floodgate->assertNotFlooding($actor);
        }

        $discussion = $this->bus->dispatch(
            new StartDiscussion($actor, Arr::get($request->getParsedBody(), 'data', []), $ipAddress)
        );

        // After creating the discussion, we assume that the user has seen all
        // of the posts in the discussion; thus, we will mark the discussion
        // as read if they are logged in.
        if ($actor->exists) {
            $this->bus->dispatch(
                new ReadDiscussion($discussion->id, $actor, 1)
            );
        }

        return $discussion;
    }
}
