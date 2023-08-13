<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Api\Controller;

use Flarum\Api\Serializer\UserSerializer;
use Flarum\Http\RequestUtil;
use Flarum\User\Command\DeleteAvatar;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Http\Request;
use Tobscure\JsonApi\Document;

class DeleteAvatarController extends AbstractShowController
{
    public ?string $serializer = UserSerializer::class;

    public function __construct(
        protected Dispatcher $bus
    ) {
    }

    protected function data(Request $request, Document $document): mixed
    {
        return $this->bus->dispatch(
            new DeleteAvatar($request->route( 'id'), RequestUtil::getActor($request))
        );
    }
}
