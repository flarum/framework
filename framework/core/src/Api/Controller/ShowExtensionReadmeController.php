<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Api\Controller;

use Flarum\Api\Serializer\ExtensionReadmeSerializer;
use Flarum\Extension\Extension;
use Flarum\Extension\ExtensionManager;
use Flarum\Http\RequestUtil;
use Illuminate\Support\Arr;
use Psr\Http\Message\ServerRequestInterface;
use Tobscure\JsonApi\Document;

class ShowExtensionReadmeController extends AbstractShowController
{
    public ?string $serializer = ExtensionReadmeSerializer::class;

    public function __construct(
        protected ExtensionManager $extensions
    ) {
    }

    protected function data(ServerRequestInterface $request, Document $document): ?Extension
    {
        $extensionName = Arr::get($request->getQueryParams(), 'name');

        RequestUtil::getActor($request)->assertAdmin();

        return $this->extensions->getExtension($extensionName);
    }
}
