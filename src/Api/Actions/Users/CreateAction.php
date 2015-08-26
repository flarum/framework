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

use Flarum\Core\Users\Commands\RegisterUser;
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
    public $serializer = 'Flarum\Api\Serializers\UserSerializer';

    /**
     * @inheritdoc
     */
    public $include = [];

    /**
     * @inheritdoc
     */
    public $link = [];

    /**
     * @inheritdoc
     */
    public $limitMax = 50;

    /**
     * @inheritdoc
     */
    public $limit = 20;

    /**
     * @inheritdoc
     */
    public $sortFields = [];

    /**
     * @inheritdoc
     */
    public $sort;

    /**
     * @param Dispatcher $bus
     */
    public function __construct(Dispatcher $bus)
    {
        $this->bus = $bus;
    }

    /**
     * Register a user according to input from the API request.
     *
     * @param JsonApiRequest $request
     * @return \Flarum\Core\Users\User
     */
    protected function create(JsonApiRequest $request)
    {
        return $this->bus->dispatch(
            new RegisterUser($request->actor, $request->get('data'))
        );
    }
}
