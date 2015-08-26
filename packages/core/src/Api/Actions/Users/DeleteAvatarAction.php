<?php
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Api\Actions\Users;

use Flarum\Core\Users\Commands\DeleteAvatar;
use Flarum\Api\Actions\SerializeResourceAction;
use Flarum\Api\JsonApiRequest;
use Illuminate\Contracts\Bus\Dispatcher;
use Tobscure\JsonApi\Document;

class DeleteAvatarAction extends SerializeResourceAction
{
    /**
     * @var Dispatcher
     */
    protected $bus;

    /**
     * @inheritdoc
     */
    public $serializer = 'Flarum\Api\Serializers\UserSerializer';

    /**
     * @param Dispatcher $bus
     */
    public function __construct(Dispatcher $bus)
    {
        $this->bus = $bus;
    }

    /**
     * Delete a user's avatar, and return the user ready to be serialized and
     * assigned to the JsonApi response.
     *
     * @param JsonApiRequest $request
     * @param Document $document
     * @return \Flarum\Core\Users\User
     */
    protected function data(JsonApiRequest $request, Document $document)
    {
        return $this->bus->dispatch(
            new DeleteAvatar($request->get('id'), $request->actor)
        );
    }
}
