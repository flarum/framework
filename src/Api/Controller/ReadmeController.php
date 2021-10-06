<?php

/*
* This file is part of Flarum.
*
* For detailed copyright and license information, please view the
* LICENSE file that was distributed with this source code.
*/

namespace Flarum\Api\Controller;

use Flarum\Api\Serializer\ReadmeSerializer;
use Flarum\Extension\ExtensionManager;
use Flarum\Http\RequestUtil;
use Illuminate\Support\Arr;
use Psr\Http\Message\ServerRequestInterface;
use s9e\TextFormatter\Bundles\Fatdown;
use stdClass;
use Tobscure\JsonApi\Document;

class ReadmeController extends AbstractShowController
{
    protected $extensions;

    public $serializer = ReadmeSerializer::class;

    public function __construct(ExtensionManager $extensions)
    {
        $this->extensions = $extensions;
    }

    protected function data(ServerRequestInterface $request, Document $document)
    {
        $extensionName = Arr::get($request->getQueryParams(), 'name');

        RequestUtil::getActor($request)->assertAdmin();

        return $this->getReadme($extensionName);
    }

    private function getReadme(string $extensionName): stdClass
    {
        $readme = new stdClass();
        $readme->id = $extensionName;

        $ext = $this->extensions->getExtension($extensionName);

        if (! $ext) {
            return $readme;
        }

        $extPath = $ext->getPath();

        if (file_exists("$extPath/README.md")) {
            $readme->content = \file_get_contents("$extPath/README.md");
        } elseif (file_exists("$extPath/README")) {
            $readme->content = \file_get_contents("$extPath/README");
        } else {
            $readme->content = null;
        }

        if ($readme->content) {
            $xml = Fatdown::parse($readme->content);
            $readme->content = Fatdown::render($xml);
        }

        return $readme;
    }
}
