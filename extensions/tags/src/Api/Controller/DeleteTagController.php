<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tags\Api\Controller;

use Flarum\Api\Controller\AbstractDeleteController;
use Flarum\Http\RequestUtil;
use Flarum\Tags\Command\DeleteTag;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Http\Request;

class DeleteTagController extends AbstractDeleteController
{
    public function __construct(
        protected Dispatcher $bus
    ) {
    }

    protected function delete(Request $request): void
    {
        $this->bus->dispatch(
            new DeleteTag($request->query('id'), RequestUtil::getActor($request))
        );
    }
}
