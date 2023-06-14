<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Api\Controller;

use Flarum\Discussion\Command\DeleteDiscussion;
use Flarum\Http\RequestUtil;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Support\Arr;
use Psr\Http\Message\ServerRequestInterface;

class DeleteDiscussionController extends AbstractDeleteController
{
    public function __construct(
        protected Dispatcher $bus
    ) {
    }

    protected function delete(ServerRequestInterface $request): void
    {
        $id = Arr::get($request->getQueryParams(), 'id');
        $actor = RequestUtil::getActor($request);
        $input = $request->getParsedBody();

        $this->bus->dispatch(
            new DeleteDiscussion($id, $actor, $input)
        );
    }
}
