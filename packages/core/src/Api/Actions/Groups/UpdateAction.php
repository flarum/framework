<?php
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Api\Actions\Groups;

use Flarum\Core\Groups\Commands\EditGroup;
use Flarum\Api\Actions\SerializeResourceAction;
use Flarum\Api\JsonApiRequest;
use Illuminate\Contracts\Bus\Dispatcher;
use Tobscure\JsonApi\Document;

class UpdateAction extends SerializeResourceAction
{
    /**
     * @var Dispatcher
     */
    protected $bus;

    /**
     * @inheritdoc
     */
    public $serializer = 'Flarum\Api\Serializers\GroupSerializer';

    /**
     * @param Dispatcher $bus
     */
    public function __construct(Dispatcher $bus)
    {
        $this->bus = $bus;
    }

    /**
     * Update a group according to input from the API request, and return it
     * ready to be serialized and assigned to the JsonApi response.
     *
     * @param JsonApiRequest $request
     * @param Document $document
     * @return \Flarum\Core\Groups\Group
     */
    protected function data(JsonApiRequest $request, Document $document)
    {
        return $this->bus->dispatch(
            new EditGroup($request->get('id'), $request->actor, $request->get('data'))
        );
    }
}
