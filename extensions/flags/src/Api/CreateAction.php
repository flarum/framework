<?php
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Flags\Api;

use Flarum\Flags\Commands\CreateFlag;
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
    public $serializer = 'Flarum\Flags\Api\FlagSerializer';

    /**
     * @inheritdoc
     */
    public $include = [
        'post' => true,
        'post.flags' => true
    ];

    /**
     * @param Dispatcher $bus
     */
    public function __construct(Dispatcher $bus)
    {
        $this->bus = $bus;
    }

    /**
     * Create a flag according to input from the API request.
     *
     * @param JsonApiRequest $request
     * @return \Flarum\Flags\Flag
     */
    protected function create(JsonApiRequest $request)
    {
        return $this->bus->dispatch(
            new CreateFlag($request->actor, $request->get('data'))
        );
    }
}
