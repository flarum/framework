<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Flags\Api\Controller;

use Flarum\Api\Controller\AbstractDeleteController;
use Flarum\Flags\Command\DeleteFlags;
use Flarum\Http\RequestUtil;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class DeleteFlagsController extends AbstractDeleteController
{
    public function __construct(
        protected Dispatcher $bus
    ) {
    }

    protected function delete(Request $request): void
    {
        $this->bus->dispatch(
            new DeleteFlags($request->query('id'), RequestUtil::getActor($request), $request->json()->all())
        );
    }
}
