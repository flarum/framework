<?php

namespace Flarum\Api\Controller;

use Carbon\Carbon;
use Flarum\Api\Serializer\CurrentUserSerializer;
use Flarum\User\User;
use Flarum\User\UserRepository;
use Illuminate\Session\Store;
use Psr\Http\Message\ServerRequestInterface;
use Tobscure\JsonApi\Document;

class ReadAllDiscussionsController extends AbstractShowController
{
    /**
     * {@inheritdoc}
     */
    public $serializer = CurrentUserSerializer::class;

    /**
     * @var \Flarum\User\UserRepository
     */
    protected $users;

    /**
     * @param \Flarum\User\UserRepository $users
     */
    public function __construct(UserRepository $users)
    {
        $this->users = $users;
    }

    /**
     * Get the data to be serialized and assigned to the response document.
     *
     * @param ServerRequestInterface $request
     * @param Document $document
     * @return mixed
     */
    protected function data(ServerRequestInterface $request, Document $document)
    {
        /**
         * @var $actor User
         */
        $actor = $request->getAttribute('actor');

        /**
         * @var $session Store
         */
        $session = $request->getAttribute('session');

        $session->put('previous_marked_all_as_read_at', $actor->marked_all_as_read_at);
        $session->put('can_cancel_marked_all_until', Carbon::now()->addSeconds(15));
        $session->save();

        $actor->markAllAsRead();
        $actor->save();

        return $this->users->findOrFail($actor->id, $actor);
    }
}
