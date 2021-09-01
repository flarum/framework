<?php

namespace SychO\PackageManager\Api\Controller;

use Flarum\Http\RequestUtil;
use SychO\PackageManager\Api\Serializer\TaskSerializer;
use SychO\PackageManager\Task;
use Flarum\Api\Controller\AbstractListController;
use Psr\Http\Message\ServerRequestInterface;
use Tobscure\JsonApi\Document;

class ListTaskController extends AbstractListController
{
    public $serializer = TaskSerializer::class;

    /**
     * @throws \Flarum\User\Exception\PermissionDeniedException
     */
    protected function data(ServerRequestInterface $request, Document $document)
    {
        RequestUtil::getActor($request)->assertAdmin();

        return Task::query()->orderBy('created_at', 'desc')->get();
    }
}
