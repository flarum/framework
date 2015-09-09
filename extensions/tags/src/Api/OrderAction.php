<?php 
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Tags\Api;

use Flarum\Api\Actions\Action;
use Flarum\Api\Request;
use Zend\Diactoros\Response\EmptyResponse;
use Flarum\Tags\Tag;
use Flarum\Core\Exceptions\PermissionDeniedException;

class OrderAction implements Action
{
    public function handle(Request $request)
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
