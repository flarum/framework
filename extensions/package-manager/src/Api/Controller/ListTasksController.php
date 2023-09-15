<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\PackageManager\Api\Controller;

use Flarum\Api\Controller\AbstractListController;
use Flarum\Http\RequestUtil;
use Flarum\Http\UrlGenerator;
use Flarum\PackageManager\Api\Serializer\TaskSerializer;
use Flarum\PackageManager\Task\TaskRepository;
use Illuminate\Http\Request;
use Tobscure\JsonApi\Document;

class ListTasksController extends AbstractListController
{
    public ?string $serializer = TaskSerializer::class;

    public function __construct(
        protected UrlGenerator $url,
        protected TaskRepository $repository
    ) {
    }

    protected function data(Request $request, Document $document): iterable
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

        $document->addMeta('total', (string) $total);

        $document->addPaginationLinks(
            $this->url->route('api.package-manager.tasks.index'),
            $request->query(),
            $offset,
            $limit,
            $total
        );

        return $results;
    }
}
