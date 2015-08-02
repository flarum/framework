<?php namespace Flarum\Tags\Api;

use Flarum\Api\Actions\JsonApiAction;
use Flarum\Api\Request;
use Zend\Diactoros\Response\EmptyResponse;
use Flarum\Tags\Tag;
use Flarum\Core\Exceptions\PermissionDeniedException;

class OrderAction extends JsonApiAction
{
    protected function respond(Request $request)
    {
        if (! $request->actor->isAdmin()) {
            throw new PermissionDeniedException;
        }

        $order = $request->get('order');

        Tag::query()->update([
            'position' => null,
            'parent_id' => null
        ]);

        foreach ($order as $i => $parent) {
            $parentId = array_get($parent, 'id');

            Tag::where('id', $parentId)->update(['position' => $i]);

            if (isset($parent['children']) && is_array($parent['children'])) {
                foreach ($parent['children'] as $j => $childId) {
                    Tag::where('id', $childId)->update([
                        'position' => $j,
                        'parent_id' => $parentId
                    ]);
                }
            }
        }

        return new EmptyResponse(204);
    }
}
