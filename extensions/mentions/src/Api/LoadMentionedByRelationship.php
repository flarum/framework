<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Mentions\Api;

use Flarum\Api\Controller\AbstractSerializeController;
use Flarum\Discussion\Discussion;
use Flarum\Http\RequestUtil;
use Flarum\Post\Post;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Apply visibility permissions to API data's mentionedBy relationship.
 * And limit mentionedBy to 3 posts only for performance reasons.
 */
class LoadMentionedByRelationship
{
    public static int $maxMentionedBy = 4;

    public static function mutateRelation(BelongsToMany $query, ServerRequestInterface $request): void
    {
        $actor = RequestUtil::getActor($request);

        $query
            ->with(['mentionsPosts', 'mentionsPosts.user', 'mentionsPosts.discussion', 'mentionsUsers'])
            ->whereVisibleTo($actor)
            ->oldest()
            // Limiting a relationship results is only possible because
            // the Post model uses the \Staudenmeir\EloquentEagerLimit\HasEagerLimit
            // trait.
            ->limit(self::$maxMentionedBy);
    }

    /**
     * Called using the @see ApiController::prepareDataForSerialization extender.
     */
    public static function countRelation(AbstractSerializeController $controller, mixed $data, ServerRequestInterface $request): array
    {
        $actor = RequestUtil::getActor($request);
        $loadable = null;

        if ($data instanceof Discussion) {
            // We do this because the ShowDiscussionController manipulates the posts
            // in a way that some of them are just ids.
            $loadable = $data->posts->filter(fn ($post) => $post instanceof Post);

            // firstPost and lastPost might have been included in the API response,
            // so we have to make sure counts are also loaded for them.
            if ($data->firstPost) {
                $loadable->push($data->firstPost);
            }

            if ($data->lastPost) {
                $loadable->push($data->lastPost);
            }
        } elseif ($data instanceof Collection) {
            $loadable = $data;
        } elseif ($data instanceof Post) {
            $loadable = $data->newCollection([$data]);
        }

        if ($loadable) {
            $loadable->loadCount([
                'mentionedBy' => fn ($query) => $query->whereVisibleTo($actor)
            ]);
        }

        return [];
    }
}
