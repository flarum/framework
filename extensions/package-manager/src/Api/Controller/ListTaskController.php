<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\PackageManager\Api\Controller;

use Flarum\Http\RequestUtil;
use Flarum\PackageManager\Api\Serializer\TaskSerializer;
use Flarum\PackageManager\Task;
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
