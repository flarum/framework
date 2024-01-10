<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\ExtensionManager\Api\Controller;

use Flarum\Api\Controller\AbstractListController;
use Flarum\Http\RequestUtil;
use Flarum\Http\UrlGenerator;
use Flarum\ExtensionManager\Api\Serializer\TaskSerializer;
use Flarum\ExtensionManager\Task\Task;
use Psr\Http\Message\ServerRequestInterface;
use Tobscure\JsonApi\Document;

class ListTasksController extends AbstractListController
{
    /**
     * {@inheritdoc}
     */
    public $serializer = TaskSerializer::class;

    /**
     * @var UrlGenerator
     */
    protected $url;

    public function __construct(UrlGenerator $url)
    {
        $this->url = $url;
    }

    protected function data(ServerRequestInterface $request, Document $document)
    {
        $actor = RequestUtil::getActor($request);

        $actor->assertAdmin();

        $limit = $this->extractLimit($request);
        $offset = $this->extractOffset($request);

        $results = Task::query()
            ->latest('id')
            ->offset($offset)
            ->limit($limit)
            ->get();

        $total = Task::query()->count();

        $document->addMeta('total', (string) $total);

        $document->addPaginationLinks(
            $this->url->to('api')->route('extension-manager.tasks.index'),
            $request->getQueryParams(),
            $offset,
            $limit,
            $total
        );

        return $results;
    }
}
