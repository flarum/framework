<?php

namespace Flarum\Api\Controller;

use Carbon\Carbon;
use Flarum\Api\Serializer\CurrentUserSerializer;
use Flarum\Foundation\ValidationException;
use Flarum\User\User;
use Flarum\User\UserRepository;
use Illuminate\Session\Store;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\Translation\TranslatorInterface;
use Tobscure\JsonApi\Document;

class ReadAllDiscussionsCancelController extends AbstractShowController
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
     * @throws ValidationException
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

        if ($session->has('can_cancel_marked_all_until') && Carbon::now()->isBefore($session->get('can_cancel_marked_all_until'))) {
            $actor->marked_all_as_read_at = $session->get('previous_marked_all_as_read_at');
            $actor->save();
        } else {
            throw new ValidationException([
                'something' => [
                    app(TranslatorInterface::class)->trans('core.api.too_late_to_cancel_read_all'),
                ],
            ]);
        }

        return $this->users->findOrFail($actor->id, $actor);
    }
}
