<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tags\Api\Controller;

use Flarum\Api\Controller\AbstractShowController;
use Flarum\Http\RequestUtil;
use Flarum\Tags\Api\Serializer\TagSerializer;
use Flarum\Tags\TagRepository;
use Illuminate\Support\Arr;
use Psr\Http\Message\ServerRequestInterface;
use Tobscure\JsonApi\Document;

class ShowTagController extends AbstractShowController
{
    public $serializer = TagSerializer::class;

    public $optionalInclude = [
        'children',
        'children.parent',
        'lastPostedDiscussion',
        'parent',
        'parent.children',
        'parent.children.parent',
        'state'
    ];

    /**
     * @var TagRepository
     */
    private $tags;

    public function __construct(TagRepository $tags)
    {
        $this->tags = $tags;
    }

    /**
     * {@inheritdoc}
     */
    protected function data(ServerRequestInterface $request, Document $document)
    {
        $slug = Arr::get($request->getQueryParams(), 'slug');
        $actor = RequestUtil::getActor($request);
        $include = $this->extractInclude($request);
        $setParentOnChildren = false;

        if (in_array('parent.children.parent', $include, true)) {
            $setParentOnChildren = true;
            $include[] = 'parent.children';
            $include = array_unique(array_diff($include, ['parent.children.parent']));
        }

        $tag = $this->tags
            ->with($include, $actor)
            ->whereVisibleTo($actor)
            ->where('slug', $slug)
            ->firstOrFail();

        if ($setParentOnChildren && $tag->parent) {
            foreach ($tag->parent->children as $child) {
                $child->parent = $tag->parent;
            }
        }

        return $tag;
    }
}
