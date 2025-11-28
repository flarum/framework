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
use Flarum\Api\Resource\PostResource;
use Flarum\Api\Resource\UserResource;
use Flarum\Database\AbstractModel;
use Flarum\Discussion\Discussion;
use Flarum\Messages\Dialog;
use Flarum\Messages\DialogMessage;
use Flarum\Post\Post;
use Flarum\User\Guest;
use Flarum\User\User;
use FoF\DiscussionViews\Listeners\AddDiscussionViewHandler;

class Generator
{
    protected array $endpoints = [
        Dialog::class => 'dialogs',
        DialogMessage::class => 'dialog-messages',
        Discussion::class => 'discussions',
        Post::class => 'posts',
        User::class => 'users',
    ];

    protected array $resources = [
        Post::class => PostResource::class,
        Discussion::class => DiscussionResource::class,
        User::class => UserResource::class,
    ];

    public function __construct(private Client $client)
    {
    }

    public function __invoke(AbstractModel $subject, ?User $recipient = null, ?array $includes = null): ?array
    {
        if ($subject instanceof Post) {
            $subject = $subject->discussion;
        }

        $this->disableTracking();

        $endpoint = $this->retrieve($subject, $this->endpoints);

        /** @var int|string|null $subjectId */
        $subjectId = $subject->getAttribute('id');

        if (! $endpoint || $subjectId === null) {
            return null;
        }

//        $include = $includes !== null ? $includes : $this->with($subject);

        $response = $this->client
            ->withActor($recipient ?? new Guest)
            // @todo disabling this and relying on default includes
//            ->withQueryParams(['include' => $include])
            ->get("/$endpoint/$subjectId");

        $contents = (string) $response->getBody();

        if ($response->getStatusCode() === 200 && ! empty($contents)) {
            return json_decode($contents, true);
        }

        return null;
    }

    protected function retrieve(AbstractModel $class, array $find): ?string
    {
        foreach ($find as $match => $result) {
            /** @phpstan-ignore-next-line */
            if (is_a($class, $match) || is_subclass_of($class, $match)) {
                return $result;
            }
        }

        return null;
    }

    protected function disableTracking(): void
    {
        // fof/discussion-views
        class_exists(AddDiscussionViewHandler::class) && AddDiscussionViewHandler::$enabled = false;
    }
}
