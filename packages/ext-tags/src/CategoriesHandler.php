<?php namespace Flarum\Categories;

use Flarum\Categories\Events\DiscussionWasMoved;
use Flarum\Core\Events\ModelCall;
use Flarum\Core\Events\RegisterDiscussionGambits;
use Flarum\Core\Events\DiscussionWillBeSaved;
use Flarum\Core\Models\Discussion;
use Flarum\Api\Events\SerializeRelationship;
use Flarum\Api\Serializers\DiscussionSerializer;
use Flarum\Forum\Events\RenderView;

class CategoriesHandler
{
    public function subscribe($events)
    {
        $events->listen('Flarum\Forum\Events\RenderView', __CLASS__.'@renderForum');
        $events->listen('Flarum\Api\Events\SerializeRelationship', __CLASS__.'@serializeRelationship');
        $events->listen('Flarum\Core\Events\RegisterDiscussionGambits', __CLASS__.'@registerDiscussionGambits');
        $events->listen('Flarum\Core\Events\DiscussionWillBeSaved', __CLASS__.'@whenDiscussionWillBeSaved');
    }

    public function renderForum(RenderView $event)
    {
        $root = __DIR__.'/..';

        $event->assets->addFile([
            $root.'/js/dist/extension.js',
            $root.'/less/categories.less'
        ]);

        $serializer = new CategorySerializer($event->action->actor);
        $event->view->data = array_merge($event->view->data, $serializer->collection(Category::orderBy('position')->get())->toArray());
    }

    public function whenDiscussionWillBeSaved(DiscussionWillBeSaved $event)
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
            $discussion->raise(new DiscussionWasMoved($discussion, $user, $oldCategoryId));
        }
    }

    public function registerDiscussionGambits(RegisterDiscussionGambits $event)
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
