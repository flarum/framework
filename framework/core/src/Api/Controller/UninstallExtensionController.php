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
use Illuminate\Http\Request;

class UninstallExtensionController extends AbstractDeleteController
{
    public function __construct(
        protected ExtensionManager $extensions
    ) {
    }

    protected function delete(Request $request): void
    {
        RequestUtil::getActor($request)->assertAdmin();

        $name = $request->route('name');

        if ($this->extensions->getExtension($name) == null) {
            return;
        }

        $this->extensions->uninstall($name);
    }
}
