<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Api\Controller;

use Flarum\Api\Serializer\DiscussionSerializer;
use Flarum\Discussion\Discussion;
use Flarum\Discussion\DiscussionRepository;
use Flarum\Http\RequestUtil;
use Flarum\Http\SlugManager;
use Flarum\Post\Post;
use Flarum\Post\PostRepository;
use Flarum\User\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Tobscure\JsonApi\Document;

class ShowDiscussionController extends AbstractShowController
{
    public ?string $serializer = DiscussionSerializer::class;

    public array $include = [
        'user',
        'posts',
        'posts.discussion',
        'posts.user',
        'posts.user.groups',
        'posts.editedUser',
        'posts.hiddenUser'
    ];

    public array $optionalInclude = [
        'user',
        'lastPostedUser',
        'firstPost',
        'lastPost'
    ];

    public function __construct(
        protected DiscussionRepository $discussions,
        protected PostRepository $posts,
        protected SlugManager $slugManager
    ) {
    }

    protected function data(Request $request, Document $document): Discussion
    {
        $discussionId = $request->route('id');
        $actor = RequestUtil::getActor($request);
        $include = $this->extractInclude($request);

        if ($request->query('bySlug', false)) {
            $discussion = $this->slugManager->forResource(Discussion::class)->fromSlug($discussionId, $actor);
        } else {
            $discussion = $this->discussions->findOrFail($discussionId, $actor);
        }

        // If posts is included or a sub relation of post is included.
        if (in_array('posts', $include) || Str::contains(implode(',', $include), 'posts.')) {
            $postRelationships = $this->getPostRelationships($include);

            $this->includePosts($discussion, $request, $postRelationships);
        }

        $this->loadRelations(new Collection([$discussion]), array_filter($include, function ($relationship) {
            return ! Str::startsWith($relationship, 'posts');
        }), $request);

        return $discussion;
    }

    private function includePosts(Discussion $discussion, Request $request, array $include): void
    {
        $actor = RequestUtil::getActor($request);
        $limit = $this->extractLimit($request);
        $offset = $this->getPostsOffset($request, $discussion, $limit);

        $allPosts = $this->loadPostIds($discussion, $actor);
        $loadedPosts = $this->loadPosts($discussion, $actor, $offset, $limit, $include, $request);

        array_splice($allPosts, $offset, $limit, $loadedPosts);

        $discussion->setRelation('posts', (new Post)->newCollection($allPosts));
    }

    private function loadPostIds(Discussion $discussion, User $actor): array
    {
        return $discussion->posts()->whereVisibleTo($actor)->orderBy('number')->pluck('id')->all();
    }

    private function getPostRelationships(array $include): array
    {
        $prefixLength = strlen($prefix = 'posts.');
        $relationships = [];

        foreach ($include as $relationship) {
            if (substr($relationship, 0, $prefixLength) === $prefix) {
                $relationships[] = substr($relationship, $prefixLength);
            }
        }

        return $relationships;
    }

    private function getPostsOffset(Request $request, Discussion $discussion, int $limit): int
    {
        $actor = RequestUtil::getActor($request);

        if (($near = $request->query('page.near')) > 1) {
            $offset = $this->posts->getIndexForNumber($discussion->id, $near, $actor);
            $offset = max(0, $offset - $limit / 2);
        } else {
            $offset = $this->extractOffset($request);
        }

        return $offset;
    }

    private function loadPosts(Discussion $discussion, User $actor, int $offset, int $limit, array $include, Request $request): array
    {
        /** @var Builder $query */
        $query = $discussion->posts()->whereVisibleTo($actor);

        $query->orderBy('number')->skip($offset)->take($limit);

        $posts = $query->get();

        /** @var Post $post */
        foreach ($posts as $post) {
            $post->setRelation('discussion', $discussion);
        }

        $this->loadRelations($posts, $include, $request);

        return $posts->all();
    }

    protected function getRelationsToLoad(Collection $models): array
    {
        $addedRelations = parent::getRelationsToLoad($models);

        if ($models->first() instanceof Discussion) {
            return $addedRelations;
        }

        return $this->getPostRelationships($addedRelations);
    }

    protected function getRelationCallablesToLoad(Collection $models): array
    {
        $addedCallableRelations = parent::getRelationCallablesToLoad($models);

        if ($models->first() instanceof Discussion) {
            return $addedCallableRelations;
        }

        $postCallableRelationships = $this->getPostRelationships(array_keys($addedCallableRelations));

        $relationCallables = array_intersect_key($addedCallableRelations, array_flip(array_map(function ($relation) {
            return "posts.$relation";
        }, $postCallableRelationships)));

        // remove posts. prefix from keys
        return array_combine(array_map(function ($relation) {
            return substr($relation, 6);
        }, array_keys($relationCallables)), array_values($relationCallables));
    }
}
