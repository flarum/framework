<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Api\Controller;

use Flarum\Extension\ExtensionManager;
use Flarum\Http\RequestUtil;
use Illuminate\Support\Arr;
use Psr\Http\Message\ServerRequestInterface;

class UninstallExtensionController extends AbstractDeleteController
{
    /**
     * @var ExtensionManager
     */
    protected $extensions;

    /**
     * @param \Flarum\Extension\ExtensionManager $extensions
     */
    public function __construct(ExtensionManager $extensions)
    {
        $this->extensions = $extensions;
    }

    protected function delete(ServerRequestInterface $request)
    {
        RequestUtil::getActor($request)->assertAdmin();

        $name = Arr::get($request->getQueryParams(), 'name');

        if ($this->extensions->getExtension($name) == null) {
            return;
        }

        $this->extensions->uninstall($name);
    }
}
