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
use Flarum\User\Command\UploadAvatar;
use Flarum\User\User;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Tobscure\JsonApi\Document;

class UploadAvatarController extends AbstractShowController
{
    public ?string $serializer = UserSerializer::class;

    public function __construct(
        protected Dispatcher $bus
    ) {
    }

    protected function data(Request $request, Document $document): User
    {
        $id = $request->route('id');
        $actor = RequestUtil::getActor($request);
        $file = $request->file('avatar');

        return $this->bus->dispatch(
            new UploadAvatar($id, $file, $actor)
        );
    }
}
