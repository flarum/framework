<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Api\Controller;

use Carbon\Carbon;
use Flarum\Formatter\Formatter;
use Flarum\Foundation\Config;
use Flarum\Foundation\ValidationException;
use Flarum\Http\RequestUtil;
use Flarum\Http\UrlGenerator;
use Flarum\Locale\TranslatorInterface;
use Flarum\Notification\MailableInterface;
use Flarum\Notification\NotificationMailer;
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\User\User;
use Illuminate\Contracts\View\Factory as ViewFactory;
use Illuminate\Support\Arr;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class ShowNotificationPreviewController implements RequestHandlerInterface
{
    public function __construct(
        protected ViewFactory $view,
        protected TranslatorInterface $translator,
        protected SettingsRepositoryInterface $settings,
        protected UrlGenerator $url,
        protected NotificationMailer $mailer,
        protected Formatter $formatter,
        protected Config $config
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $actor = RequestUtil::getActor($request);
        $actor->assertAdmin();

        $blueprintClass = Arr::get($request->getQueryParams(), 'blueprint');
        $driver = Arr::get($request->getQueryParams(), 'driver', 'email-html');

        if (! $blueprintClass) {
            throw new ValidationException(['blueprint' => 'Blueprint class is required']);
        }

        if (! class_exists($blueprintClass)) {
            throw new ValidationException(['blueprint' => 'Blueprint class does not exist']);
        }

        // Create a mock blueprint instance with sample data
        $mockBlueprint = $this->createMockBlueprint($blueprintClass, $actor);

        $response = [
            'type' => 'notification-preview',
            'driver' => $driver,
        ];

        if ($driver === 'email-html' || $driver === 'email-plain') {
            if (! $mockBlueprint instanceof MailableInterface) {
                throw new ValidationException(['driver' => 'This notification type does not support email']);
            }

            $views = $mockBlueprint->getEmailViews();
            $viewName = $driver === 'email-html' ? $views['html'] : $views['text'];

            // Prepare email data
            $data = $this->getEmailData($mockBlueprint, $actor);
            $this->view->share($data);

            $content = $this->view->make($viewName, $data)->render();

            $response['content'] = $content;
            $response['subject'] = $mockBlueprint->getEmailSubject($this->translator);
        } else {
            // For alert driver, return the notification data structure
            $response['content'] = $mockBlueprint->getData();
        }

        return new JsonResponse($response);
    }

    /**
     * Create a mock blueprint instance with sample data.
     */
    protected function createMockBlueprint(string $blueprintClass, User $actor): object
    {
        try {
            $reflection = new \ReflectionClass($blueprintClass);
            $constructor = $reflection->getConstructor();

            if (!$constructor) {
                return new $blueprintClass();
            }

            // Create mock arguments - try to instantiate actual classes with dummy data
            $args = [];
            foreach ($constructor->getParameters() as $param) {
                $type = $param->getType();

                if (!$type || $type->isBuiltin()) {
                    $args[] = null;
                    continue;
                }

                $typeName = $type instanceof \ReflectionNamedType ? $type->getName() : null;

                if (!$typeName) {
                    $args[] = null;
                    continue;
                }

                // Use the actor for User types, otherwise try to instantiate the actual class
                if ($typeName === User::class) {
                    $args[] = $actor;
                } else {
                    $args[] = $this->createMockInstance($typeName, $actor);
                }
            }

            return new $blueprintClass(...$args);
        } catch (\Exception $e) {
            throw new ValidationException([
                'blueprint' => 'Unable to create preview for this notification type: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Create a mock instance of the specified class with dummy data.
     */
    protected function createMockInstance(string $className, User $actor): object
    {
        try {
            $reflection = new \ReflectionClass($className);

            // Try to instantiate the class without calling the constructor
            if ($reflection->isInstantiable()) {
                $instance = $reflection->newInstanceWithoutConstructor();

                // Populate with dummy data using magic mock approach
                $this->populateMockInstance($instance, $actor);

                return $instance;
            }
        } catch (\Exception) {
            // If we can't instantiate, fall back to magic mock
        }

        // Fallback to magic mock for non-instantiable classes
        return $this->createMagicMock($actor);
    }

    /**
     * Populate a mock instance with dummy data.
     */
    protected function populateMockInstance(object $instance, User $actor): void
    {
        // Set common properties that models typically have
        $commonProperties = [
            'id' => 999,
            'user_id' => $actor->id,
            'user' => $actor,
            // Use string slugs for *_id properties as they're used in URL generation
            'discussion_id' => 'sample-preview-slug',
            'dialog_id' => 'sample-preview-slug',
            'post_id' => 'sample-preview-slug',
            'slug' => 'sample-preview-slug',
            'discussion' => $this->createMagicMock($actor),
            'post' => $this->createMagicMock($actor),
            'created_at' => Carbon::now(),
            'number' => 1,
            'exists' => true,
            'content' => ['Old Title', 'New Title'], // For event posts
        ];

        foreach ($commonProperties as $property => $value) {
            try {
                // Try to set the property if it exists
                if (property_exists($instance, $property)) {
                    $instance->$property = $value;
                }
            } catch (\Exception) {
                // Ignore if property can't be set
            }
        }
    }

    /**
     * Create a magic mock object that returns sensible dummy data for any property or method.
     */
    protected function createMagicMock(User $actor): object
    {
        return new class($actor, $this->formatter) {
            private User $actor;
            private Formatter $formatter;
            private array $cache = [];

            // Public properties that can be accessed directly
            public int $id;
            public int $user_id;
            public User $user;
            public Carbon $created_at;
            public bool $exists;

            public function __construct(User $actor, Formatter $formatter)
            {
                $this->actor = $actor;
                $this->formatter = $formatter;

                // Set up common properties
                $this->id = 999;
                $this->user_id = $actor->id;
                $this->user = $actor;
                $this->created_at = Carbon::now();
                $this->exists = true;
            }

            public function __get($name)
            {
                // Return cached value if already accessed
                if (isset($this->cache[$name])) {
                    return $this->cache[$name];
                }

                // Generate sensible dummy data based on property name
                $value = match (true) {
                    $name === 'id' => 999,
                    $name === 'user' => $this->actor,
                    $name === 'user_id' => $this->actor->id,
                    $name === 'slug' => 'sample-preview-slug',
                    // For *_id properties (used in URL generation), always return a string slug
                    // This catches discussion_id, dialog_id, etc. which are used in route() calls
                    str_ends_with($name, '_id') => 'sample-preview-slug',
                    $name === 'title' => 'Sample Preview Title',
                    $name === 'content' => '<t><p>This is sample content for the notification preview.</p></t>',
                    $name === 'display_name' => $this->actor->display_name,
                    $name === 'username' => $this->actor->username,
                    $name === 'number' => 1,
                    $name === 'discussion' => $this->createNestedMock(),
                    $name === 'post' => $this->createNestedMock(),
                    $name === 'reply' => $this->createNestedMock(),
                    $name === 'message' => $this->createNestedMock(),
                    $name === 'dialog' => $this->createNestedMock(),
                    str_ends_with($name, '_at') => Carbon::now(),
                    str_ends_with($name, '_count') => 5,
                    default => $this->createNestedMock(),
                };

                $this->cache[$name] = $value;
                return $value;
            }

            public function __call($method, $args)
            {
                // Handle common method calls
                return match (true) {
                    $method === 'formatContent' => '<p>This is formatted sample content for the notification preview.</p>',
                    $method === 'getAttribute' => $this->__get($args[0] ?? 'id'),
                    default => null,
                };
            }

            private function createNestedMock(): object
            {
                // Create another magic mock for nested objects
                return new self($this->actor, $this->formatter);
            }
        };
    }

    /**
     * Get email template data.
     */
    protected function getEmailData(MailableInterface $blueprint, User $user): array
    {
        $type = $blueprint::getType();

        return [
            'blueprint' => $blueprint,
            'user' => $user,
            'username' => $user->username,
            'userEmail' => $user->email,
            'forumTitle' => $this->settings->get('forum_title'),
            'type' => $type,
            'unsubscribeLink' => $this->url->to('forum')->route('notifications.unsubscribe', [
                'userId' => $user->id,
                'token' => 'PREVIEW_TOKEN'
            ]),
            'settingsLink' => $this->url->to('forum')->route('settings'),
            'url' => $this->url,
            'settings' => $this->settings,
            'translator' => $this->translator,
            'formatter' => $this->formatter,
        ];
    }
}
