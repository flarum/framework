<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Api\Controller;

use Flarum\Http\RequestUtil;
use Flarum\User\Command\DeleteUser;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Http\Request;

class DeleteUserController extends AbstractDeleteController
{
    public function __construct(
        protected Dispatcher $bus
    ) {
    }

    protected function delete(Request $request): void
    {
        $this->bus->dispatch(
            new DeleteUser($request->route('id'), RequestUtil::getActor($request))
        );
    }
}
