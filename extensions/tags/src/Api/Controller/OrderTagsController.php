<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tags\Api\Controller;

use Flarum\Http\RequestUtil;
use Flarum\Tags\Tag;
use Illuminate\Support\Arr;
use Laminas\Diactoros\Response\EmptyResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class OrderTagsController implements RequestHandlerInterface
{
    /**
     * {@inheritdoc}
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        RequestUtil::getActor($request)->assertAdmin();

        $order = Arr::get($request->getParsedBody(), 'order');

        if ($order === null) {
            return new EmptyResponse(422);
        }

        Tag::query()->update([
            'position' => null,
            'parent_id' => null
        ]);

        foreach ($order as $i => $parent) {
            $parentId = Arr::get($parent, 'id');

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
