<?php namespace Flarum\Categories\Handlers;

use Flarum\Api\Events\SerializeRelationship;
use Flarum\Api\Serializers\DiscussionSerializer;
use Flarum\Support\Actor;
use Flarum\Forum\Events\RenderView;
use Flarum\Core\Events\ModelCall;
use Flarum\Core\Events\RegisterDiscussionGambits;
use Flarum\Core\Models\Discussion;
use Flarum\Categories\Category;
use Flarum\Categories\CategorySerializer;
use Flarum\Categories\DiscussionMovedPost;
use Flarum\Core\Events\DiscussionWillBeSaved;

class Handler
{
    public function __construct(Actor $actor)
    {
        $this->actor = $actor;
    }

    public function subscribe($events)
    {
        $events->listen('Flarum\Forum\Events\RenderView', __CLASS__.'@renderForum');
        $events->listen('Flarum\Api\Events\SerializeRelationship', __CLASS__.'@serializeRelationship');
        $events->listen('Flarum\Core\Events\RegisterDiscussionGambits', __CLASS__.'@registerGambits');
        $events->listen('Flarum\Core\Events\DiscussionWillBeSaved', __CLASS__.'@saveCategoryToDiscussion');
    }

    public function renderForum(RenderView $event)
    {
        $root = __DIR__.'/../..';

        $event->assets->addFile([
            $root.'/js/dist/extension.js',
            $root.'/less/categories.less'
        ]);

        $serializer = new CategorySerializer($event->action->actor);
        $event->view->data = array_merge($event->view->data, $serializer->collection(Category::orderBy('position')->get())->toArray());
    }

    public function saveCategoryToDiscussion(DiscussionWillBeSaved $event)
    {
        if (isset($event->command->data['links']['category']['linkage'])) {
            $linkage = $event->command->data['links']['category']['linkage'];

            $categoryId = (int) $linkage['id'];
            $discussion = $event->discussion;
            $user = $event->command->user;

            $oldCategoryId = (int) $discussion->category_id;

            if ($oldCategoryId === $categoryId) {
                return;
            }

            $discussion->category_id = $categoryId;

            if ($discussion->exists) {
                $lastPost = $discussion->posts()->orderBy('time', 'desc')->first();
                if ($lastPost instanceof DiscussionMovedPost) {
                    if ($lastPost->content[0] == $categoryId) {
                        $lastPost->delete();
                        $discussion->postWasRemoved($lastPost);
                    } else {
                        $newContent = $lastPost->content;
                        $newContent[1] = $categoryId;
                        $lastPost->content = $newContent;
                        $lastPost->save();
                        $discussion->postWasAdded($lastPost);
                    }
                } else {
                    $post = DiscussionMovedPost::reply(
                        $discussion->id,
                        $user->id,
                        $oldCategoryId,
                        $categoryId
                    );

                    $post->save();

                    $discussion->postWasAdded($post);
                }
            }
        }
    }

    public function registerGambits(RegisterDiscussionGambits $event)
    {
        $event->gambits->add('Flarum\Categories\CategoryGambit');
    }

    public function serializeRelationship(SerializeRelationship $event)
    {
        if ($event->serializer instanceof DiscussionSerializer && $event->name === 'category') {
            return $event->serializer->hasOne('Flarum\Categories\CategorySerializer', 'category');
        }
    }
}
