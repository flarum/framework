<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Tags\Api\Controller;

use Flarum\Api\Controller\AbstractCollectionController;
use Flarum\Tags\Api\Serializer\TagSerializer;
use Flarum\Tags\TagRepository;
use Psr\Http\Message\ServerRequestInterface;
use Tobscure\JsonApi\Document;

class ListTagsController extends AbstractCollectionController
{
    /**
     * {@inheritdoc}
     */
    public $serializer = TagSerializer::class;

    /**
     * @var \Flarum\Tags\TagRepository
     */
    protected $tags;

    /**
     * @param \Flarum\Tags\TagRepository $tags
     */
    public function __construct(TagRepository $tags)
    {
        $this->tags = $tags;
    }

    /**
     * {@inheritdoc}
     */
    protected function data(ServerRequestInterface $request, Document $document)
    {
        return $this->tags->all();
    }
}
