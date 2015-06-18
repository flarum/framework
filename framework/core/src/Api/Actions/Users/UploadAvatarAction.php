<?php namespace Flarum\Api\Actions\Users;

use Flarum\Core\Commands\UploadAvatarCommand;
use Flarum\Api\Actions\SerializeResourceAction;
use Flarum\Api\JsonApiRequest;
use Illuminate\Contracts\Bus\Dispatcher;
use Tobscure\JsonApi\Document;

class UploadAvatarAction extends SerializeResourceAction
{
    /**
     * @var \Illuminate\Contracts\Bus\Dispatcher
     */
    protected $bus;

    /**
     * @inheritdoc
     */
    public static $serializer = 'Flarum\Api\Serializers\UserSerializer';

    /**
     * @inheritdoc
     */
    public static $include = [];

    /**
     * @inheritdoc
     */
    public static $link = [];

    /**
     * @inheritdoc
     */
    public static $limitMax = 50;

    /**
     * @inheritdoc
     */
    public static $limit = 20;

    /**
     * @inheritdoc
     */
    public static $sortFields = [];

    /**
     * @inheritdoc
     */
    public static $sort;

    /**
     * Instantiate the action.
     *
     * @param \Illuminate\Contracts\Bus\Dispatcher $bus
     */
    public function __construct(Dispatcher $bus)
    {
        $this->bus = $bus;
    }

    /**
     * Upload an avatar for a user, and return the user ready to be serialized
     * and assigned to the JsonApi response.
     *
     * @param \Flarum\Api\JsonApiRequest $request
     * @param \Tobscure\JsonApi\Document $document
     * @return \Flarum\Core\Models\Discussion
     */
    protected function data(JsonApiRequest $request, Document $document)
    {
        return $this->bus->dispatch(
            new UploadAvatarCommand(
                $request->get('id'),
                $request->http->getUploadedFiles()['avatar'],
                $request->actor->getUser()
            )
        );
    }
}
