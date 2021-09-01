<?php

namespace SychO\PackageManager\Api\Controller;

use Flarum\Bus\Dispatcher;
use Flarum\Http\RequestUtil;
use SychO\PackageManager\Api\Serializer\ExtensionSerializer;
use Flarum\Api\Controller\AbstractShowController;
use Illuminate\Support\Arr;
use Psr\Http\Message\ServerRequestInterface;
use SychO\PackageManager\Command\UpdateExtension;
use Tobscure\JsonApi\Document;

class UpdateExtensionController extends AbstractShowController
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
    protected function data(ServerRequestInterface $request, Document $document)
    {
        $actor = RequestUtil::getActor($request);
        $extensionId = Arr::get($request->getQueryParams(), 'id');

        return $this->bus->dispatch(
            new UpdateExtension($actor, $extensionId)
        );
    }
}
