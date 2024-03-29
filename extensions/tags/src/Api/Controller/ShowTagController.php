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
use Flarum\Http\SlugManager;
use Flarum\Tags\Api\Serializer\TagSerializer;
use Flarum\Tags\Tag;
use Flarum\Tags\TagRepository;
use Illuminate\Support\Arr;
use Psr\Http\Message\ServerRequestInterface;
use Tobscure\JsonApi\Document;

class ShowTagController extends AbstractShowController
{
    public ?string $serializer = TagSerializer::class;

    public array $optionalInclude = [
        'children',
        'children.parent',
        'lastPostedDiscussion',
        'parent',
        'parent.children',
        'parent.children.parent',
        'state'
    ];

    public function __construct(
        protected TagRepository $tags,
        protected SlugManager $slugger
    ) {
    }

    protected function data(ServerRequestInterface $request, Document $document): Tag
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

        $tag = $this->slugger
            ->forResource(Tag::class)
            ->fromSlug($slug, $actor);

        $tag->load($this->tags->getAuthorizedRelations($include, $actor));

        if ($setParentOnChildren && $tag->parent) {
            foreach ($tag->parent->children as $child) {
                $child->parent = $tag->parent;
            }
        }

        return $tag;
    }
}
