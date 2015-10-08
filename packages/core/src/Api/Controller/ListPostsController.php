<?php
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Api\Controller;

use Flarum\Core\Repository\PostRepository;
use Psr\Http\Message\ServerRequestInterface;
use Tobscure\JsonApi\Document;
use Tobscure\JsonApi\Exception\InvalidParameterException;

class ListPostsController extends AbstractCollectionController
{
    /**
     * {@inheritdoc}
     */
    public $serializer = 'Flarum\Api\Serializer\PostSerializer';

    /**
     * {@inheritdoc}
     */
    public $include = [
        'user',
        'user.groups',
        'editUser',
        'hideUser',
        'discussion'
    ];

    /**
     * {@inheritdoc}
     */
    public $sortFields = ['time'];

    /**
     * @var \Flarum\Core\Repository\PostRepository
     */
    private $posts;

    /**
     * @param \Flarum\Core\Repository\PostRepository $posts
     */
    public function __construct(PostRepository $posts)
    {
        $this->posts = $posts;
    }

    /**
     * {@inheritdoc}
     */
    protected function data(ServerRequestInterface $request, Document $document)
    {
        $actor = $request->getAttribute('actor');
        $filter = $this->extractFilter($request);
        $include = $this->extractInclude($request);
        $where = [];

        if ($postIds = array_get($filter, 'id')) {
            $posts = $this->posts->findByIds(explode(',', $postIds), $actor);
        } else {
            if ($discussionId = array_get($filter, 'discussion')) {
                $where['discussion_id'] = $discussionId;
            }
            if ($number = array_get($filter, 'number')) {
                $where['number'] = $number;
            }
            if ($userId = array_get($filter, 'user')) {
                $where['user_id'] = $userId;
            }
            if ($type = array_get($filter, 'type')) {
                $where['type'] = $type;
            }

            $posts = $this->getPosts($request, $where);
        }

        return $posts->load($include);
    }

    /**
     * @param ServerRequestInterface $request
     * @param array $where
     * @return \Illuminate\Database\Eloquent\Collection
     * @throws InvalidParameterException
     */
    private function getPosts(ServerRequestInterface $request, array $where)
    {
        $queryParams = $request->getQueryParams();
        $actor = $request->getAttribute('actor');
        $sort = $this->extractSort($request);
        $limit = $this->extractLimit($request);

        if (($near = array_get($queryParams, 'page.near')) > 1) {
            if (count($where) > 1 || ! isset($where['discussion_id']) || $sort) {
                throw new InvalidParameterException('You can only use page[near] with '
                    . 'filter[discussion] and the default sort order');
            }

            $offset = $this->posts->getIndexForNumber($where['discussion_id'], $near, $actor);
            $offset = max(0, $offset - $limit / 2);
        } else {
            $offset = $this->extractOffset($request);
        }

        return $this->posts->findWhere($where, $actor, $sort, $limit, $offset);
    }
}
