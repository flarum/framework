<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\PackageManager\Api\Controller;

use Flarum\Api\Controller\AbstractDeleteController;
use Flarum\Bus\Dispatcher;
use Flarum\Http\RequestUtil;
use Illuminate\Support\Arr;
use Flarum\PackageManager\Api\Serializer\ExtensionSerializer;
use Psr\Http\Message\ServerRequestInterface;
use Flarum\PackageManager\Command\RemoveExtension;

class RemoveExtensionController extends AbstractDeleteController
{
    public $serializer = ExtensionSerializer::class;

    /**
     * @var Dispatcher
     */
    protected $bus;

    public function __construct(Dispatcher $bus)
    {
        $this->bus = $bus;
    }

    /**
     * @throws \Flarum\User\Exception\PermissionDeniedException
     */
    protected function delete(ServerRequestInterface $request)
    {
        $actor = RequestUtil::getActor($request);
        $extensionId = Arr::get($request->getQueryParams(), 'id');

        $this->bus->dispatch(
            new RemoveExtension($actor, $extensionId)
        );
    }
}
