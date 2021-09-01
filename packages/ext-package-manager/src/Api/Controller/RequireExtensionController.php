<?php

namespace SychO\PackageManager\Api\Controller;

use Flarum\Bus\Dispatcher;
use SychO\PackageManager\Api\Serializer\ExtensionSerializer;
use SychO\PackageManager\Command\RequireExtension;
use SychO\PackageManager\Extension\ExtensionUtils;
use Flarum\Api\Controller\AbstractCreateController;
use Flarum\Http\RequestUtil;
use Illuminate\Support\Arr;
use Psr\Http\Message\ServerRequestInterface;
use Tobscure\JsonApi\Document;

class RequireExtensionController extends AbstractCreateController
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

    protected function data(ServerRequestInterface $request, Document $document)
    {
        $actor = RequestUtil::getActor($request);
        $package = Arr::get($request->getParsedBody(), 'data.package');

        return $this->bus->dispatch(
            new RequireExtension($actor, $package)
        );
    }
}
