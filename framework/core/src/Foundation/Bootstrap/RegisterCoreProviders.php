<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Foundation\Bootstrap;

use Flarum\Admin\AdminServiceProvider;
use Flarum\Api\ApiServiceProvider;
use Flarum\Bus\BusServiceProvider;
use Flarum\Console\ConsoleServiceProvider;
use Flarum\Discussion\DiscussionServiceProvider;
use Flarum\Extension\ExtensionServiceProvider;
use Flarum\Filesystem\FilesystemServiceProvider;
use Flarum\Filter\FilterServiceProvider;
use Flarum\Formatter\FormatterServiceProvider;
use Flarum\Forum\ForumServiceProvider;
use Flarum\Frontend\FrontendServiceProvider;
use Flarum\Group\GroupServiceProvider;
use Flarum\Http\HttpServiceProvider;
use Flarum\Locale\LocaleServiceProvider;
use Flarum\Mail\MailServiceProvider;
use Flarum\Notification\NotificationServiceProvider;
use Flarum\Post\PostServiceProvider;
use Flarum\Queue\QueueServiceProvider;
use Flarum\Search\SearchServiceProvider;
use Flarum\Update\UpdateServiceProvider;
use Flarum\User\SessionServiceProvider;
use Flarum\User\UserServiceProvider;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Hashing\HashServiceProvider;
use Illuminate\Validation\ValidationServiceProvider;
use Illuminate\View\ViewServiceProvider;

class RegisterCoreProviders implements IlluminateBootstrapperInterface
{
    public function bootstrap(Application $app): void
    {
        $app->register(AdminServiceProvider::class);
        $app->register(ApiServiceProvider::class);
        $app->register(BusServiceProvider::class);
        $app->register(ConsoleServiceProvider::class);
        $app->register(DiscussionServiceProvider::class);
        $app->register(ExtensionServiceProvider::class);
        $app->register(FilesystemServiceProvider::class);
        $app->register(FilterServiceProvider::class);
        $app->register(FormatterServiceProvider::class);
        $app->register(ForumServiceProvider::class);
        $app->register(FrontendServiceProvider::class);
        $app->register(GroupServiceProvider::class);
        $app->register(HashServiceProvider::class);
        $app->register(HttpServiceProvider::class);
        $app->register(LocaleServiceProvider::class);
        $app->register(MailServiceProvider::class);
        $app->register(NotificationServiceProvider::class);
        $app->register(PostServiceProvider::class);
        $app->register(QueueServiceProvider::class);
        $app->register(SearchServiceProvider::class);
        $app->register(SessionServiceProvider::class);
        $app->register(UpdateServiceProvider::class);
        $app->register(UserServiceProvider::class);
        $app->register(ValidationServiceProvider::class);
        $app->register(ViewServiceProvider::class);
    }
}
