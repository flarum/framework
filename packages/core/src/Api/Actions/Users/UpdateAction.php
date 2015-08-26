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

use Flarum\Core\Users\Commands\EditUser;
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
    public $serializer = 'Flarum\Api\Serializers\CurrentUserSerializer';

    /**
     * @inheritdoc
     */
    public $include = [
        'groups' => true
    ];

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
     * Update a user according to input from the API request, and return it
     * ready to be serialized and assigned to the JsonApi response.
     *
     * @param JsonApiRequest $request
     * @param Document $document
     * @return \Flarum\Core\Users\User
     */
    protected function data(JsonApiRequest $request, Document $document)
    {
        return $this->bus->dispatch(
            new EditUser($request->get('id'), $request->actor, $request->get('data'))
        );
    }
}
