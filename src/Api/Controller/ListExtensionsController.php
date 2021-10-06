<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Api\Controller;

use Flarum\Api\Serializer\BasicExtensionSerializer;
use Flarum\Extension\ExtensionManager;
use Flarum\Http\RequestUtil;
use Psr\Http\Message\ServerRequestInterface;
use Tobscure\JsonApi\Document;

class ListExtensionsController extends AbstractListController
{
    /**
     * @var ExtensionManager
     */
    protected $extensions;
    
    /**
     * {@inheritdoc}
     */
    public $serializer = BasicExtensionSerializer::class;

    public function __construct(ExtensionManager $extensions)
    {
        $this->extensions = $extensions;
    }

    /**
     * {@inheritDoc}
     */
    protected function data(ServerRequestInterface $request, Document $document)
    {
        RequestUtil::getActor($request)->assertAdmin();

        return $this->extensions->getExtensions();
    }
}
