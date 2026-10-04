<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Realtime\Push\Payload;

use Flarum\Api\Client;
use Flarum\Api\Resource\DiscussionResource;
use Flarum\Api\Resource\NotificationResource;
use Flarum\Api\Resource\PostResource;
use Flarum\Api\Resource\UserResource;
use Flarum\Database\AbstractModel;
use Flarum\Discussion\Discussion;
use Flarum\Extension\ExtensionManager;
use Flarum\Notification\Notification;
use Flarum\Post\Post;
use Flarum\Realtime\Push\RealtimeRegistry;
use Flarum\User\Guest;
use Flarum\User\User;

class Generator
{
    /**
     * Core model→endpoint mappings. Extensions may add to this via the
     * Realtime extender's registerModelEndpoint() method.
     */
    protected array $endpoints = [
        Discussion::class => 'discussions',
        Post::class => 'posts',
        User::class => 'users',
        Notification::class => 'notifications',
    ];

    protected array $resources = [
        Post::class => PostResource::class,
        Discussion::class => DiscussionResource::class,
        User::class => UserResource::class,
        Notification::class => NotificationResource::class,
    ];

    public function __construct(
        private Client $client,
        private RealtimeRegistry $registry,
    ) {
    }

    public function __invoke(AbstractModel $subject, ?User $recipient = null, ?array $includes = null): ?array
    {
        // Merge extension-registered endpoints at call time so they are
        // available even if registered after this class was first constructed.
        $endpoints = array_merge($this->endpoints, $this->registry->getModelEndpoints());

        $post = null;

        if ($subject instanceof Post) {
            $post = $subject;
            $subject = $subject->discussion;
        }

        $endpoint = $this->retrieve($subject, $endpoints);

        /** @var int|string|null $subjectId */
        $subjectId = $subject->getAttribute('id');

        if (! $endpoint || $subjectId === null) {
            return null;
        }

        $request = $this->client->withActor($recipient ?? new Guest);

        if ($includes) {
            $request = $request->withQueryParams(['include' => implode(',', $includes)]);
        }

        $response = $request->get("/$endpoint/$subjectId");

        $contents = (string) $response->getBody();
        $decodedContents = json_decode($contents, true);

        if ($post) {
            $postResponse = $this->client
                ->withActor($recipient ?? new Guest)
                ->withQueryParams([
                    'include' => implode(',', $this->postIncludes()),
                ])
                ->get('/posts/'.$post->id);

            // Its discussion alone would still announce activity the recipient
            // isn't allowed to know about, such as a reply awaiting approval.
            if ($postResponse->getStatusCode() !== 200) {
                return null;
            }

            $postContents = (string) $postResponse->getBody();
            $decodedPostContents = json_decode($postContents, true);

            if (isset($decodedPostContents['data'])) {
                $decodedContents['included'][] = $decodedPostContents['data'];
            }
        }

        if ($response->getStatusCode() === 200 && ! empty($contents)) {
            return $decodedContents;
        }

        return null;
    }

    /**
     * An include the API doesn't know fails the whole request, so `likes` is
     * only asked for while flarum/likes is enabled.
     *
     * @return string[]
     */
    protected function postIncludes(): array
    {
        $includes = ['user', 'editedUser'];

        if (resolve(ExtensionManager::class)->isEnabled('flarum-likes')) {
            $includes[] = 'likes';
        }

        return $includes;
    }

    protected function retrieve(AbstractModel $model, array $map): ?string
    {
        foreach ($map as $class => $result) {
            if (is_string($class) && $model instanceof $class) {
                return $result;
            }
        }

        return null;
    }
}
