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

use Flarum\Core\Groups\Commands\CreateGroup;
use Flarum\Api\Actions\CreateAction as BaseCreateAction;
use Flarum\Api\JsonApiRequest;
use Illuminate\Contracts\Bus\Dispatcher;

class CreateAction extends BaseCreateAction
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
     * Create a group according to input from the API request.
     *
     * @param JsonApiRequest $request
     * @return \Flarum\Core\Groups\Group
     */
    protected function create(JsonApiRequest $request)
    {
        return $this->bus->dispatch(
            new CreateGroup($request->actor, $request->get('data'))
        );
    }
}
