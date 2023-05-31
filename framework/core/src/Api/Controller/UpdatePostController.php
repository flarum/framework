<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Api\Controller;

use Flarum\Api\Serializer\PostSerializer;
use Flarum\Http\RequestUtil;
use Flarum\Post\Command\EditPost;
use Flarum\Post\Post;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Support\Arr;
use Psr\Http\Message\ServerRequestInterface;
use Tobscure\JsonApi\Document;

class UpdatePostController extends AbstractShowController
{
    public ?string $serializer = PostSerializer::class;

    public array $include = [
        'editedUser',
        'discussion'
    ];

    public function __construct(
        protected Dispatcher $bus
    ) {
    }

    protected function data(ServerRequestInterface $request, Document $document): Post
    {
        $id = Arr::get($request->getQueryParams(), 'id');
        $actor = RequestUtil::getActor($request);
        $data = Arr::get($request->getParsedBody(), 'data', []);

        $post = $this->bus->dispatch(
            new EditPost($id, $actor, $data)
        );

        $this->loadRelations($post->newCollection([$post]), $this->extractInclude($request), $request);

        return $post;
    }
}
