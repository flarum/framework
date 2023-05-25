<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Api\Controller;

use Flarum\Group\Command\DeleteGroup;
use Flarum\Http\RequestUtil;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Support\Arr;
use Psr\Http\Message\ServerRequestInterface;

class DeleteGroupController extends AbstractDeleteController
{
    public function __construct(
        protected Dispatcher $bus
    ) {
    }

    protected function delete(ServerRequestInterface $request): void
    {
        $this->bus->dispatch(
            new DeleteGroup(Arr::get($request->getQueryParams(), 'id'), RequestUtil::getActor($request))
        );
    }
}
