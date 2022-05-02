<?php

namespace Flarum\PackageManager\Api\Controller;

use Flarum\Api\Controller\AbstractListController;
use Flarum\Http\RequestUtil;
use Flarum\Http\UrlGenerator;
use Flarum\PackageManager\Task\TaskRepository;
use Psr\Http\Message\ServerRequestInterface;
use Tobscure\JsonApi\Document;
use Flarum\PackageManager\Api\Serializer\TaskSerializer;

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

    /**
     * @var TaskRepository
     */
    protected $repository;

    public function __construct(UrlGenerator $url, TaskRepository $repository)
    {
        $this->url = $url;
        $this->repository = $repository;
    }

    protected function data(ServerRequestInterface $request, Document $document)
    {
        $actor = RequestUtil::getActor($request);

        $actor->assertAdmin();

        $limit = $this->extractLimit($request);
        $offset = $this->extractOffset($request);

        $results = $this->repository
            ->query()
            ->latest()
            ->offset($offset)
            ->limit($limit)
            ->get();

        $total = $this->repository->query()->count();

        $document->addMeta('total', $total);

        $document->addPaginationLinks(
            $this->url->to('api')->route('package-manager.tasks.index'),
            $request->getQueryParams(),
            $offset,
            $limit,
            $total
        );

        return $results;
    }
}
