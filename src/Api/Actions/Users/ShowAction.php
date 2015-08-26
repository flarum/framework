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

use Flarum\Core\Users\UserRepository;
use Flarum\Api\Actions\SerializeResourceAction;
use Flarum\Api\JsonApiRequest;
use Tobscure\JsonApi\Document;

class ShowAction extends SerializeResourceAction
{
    /**
     * @var UserRepository
     */
    protected $users;

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
     * @param UserRepository $users
     */
    public function __construct(UserRepository $users)
    {
        $this->users = $users;
    }

    /**
     * Get a single user, ready to be serialized and assigned to the JsonApi
     * response.
     *
     * @param JsonApiRequest $request
     * @param Document $document
     * @return \Flarum\Core\Users\User
     */
    protected function data(JsonApiRequest $request, Document $document)
    {
        $id = $request->get('id');

        if (! is_numeric($id)) {
            $id = $this->users->getIdForUsername($id);
        }

        return $this->users->findOrFail($id, $request->actor);
    }
}
