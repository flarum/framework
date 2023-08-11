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
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Tobscure\JsonApi\Document;

class ShowExtensionReadmeController extends AbstractShowController
{
    public ?string $serializer = ExtensionReadmeSerializer::class;

    public function __construct(
        protected ExtensionManager $extensions
    ) {
    }

    protected function data(Request $request, Document $document): ?Extension
    {
        $extensionName = $request->query('name');

        RequestUtil::getActor($request)->assertAdmin();

        return $this->extensions->getExtension($extensionName);
    }
}
