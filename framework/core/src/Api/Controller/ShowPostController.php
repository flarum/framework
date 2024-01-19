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
use Flarum\Post\Post;
use Flarum\Post\PostRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Arr;
use Psr\Http\Message\ServerRequestInterface;
use Tobscure\JsonApi\Document;

class ShowPostController extends AbstractShowController
{
    public ?string $serializer = PostSerializer::class;

    public array $include = [
        'user',
        'user.groups',
        'editedUser',
        'hiddenUser',
        'discussion'
    ];

    public function __construct(
        protected PostRepository $posts
    ) {
    }

    protected function data(ServerRequestInterface $request, Document $document): Post
    {
        $post = $this->posts->findOrFail(Arr::get($request->getQueryParams(), 'id'), RequestUtil::getActor($request));

        $include = $this->extractInclude($request);

        $this->loadRelations(new Collection([$post]), $include, $request);

        return $post;
    }
}
