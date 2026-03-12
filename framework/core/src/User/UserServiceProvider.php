<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\User;

use Flarum\Discussion\Access\DiscussionPolicy;
use Flarum\Discussion\Discussion;
use Flarum\Foundation\AbstractServiceProvider;
use Flarum\Foundation\ContainerUtil;
use Flarum\Group\Access\GroupPolicy;
use Flarum\Group\Group;
use Flarum\Http\Access\AccessTokenPolicy;
use Flarum\Http\AccessToken;
use Flarum\Post\Access\PostPolicy;
use Flarum\Post\Post;
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\User\Access\ScopeUserVisibility;
use Flarum\User\Avatar\DefaultDriver as AvatarDefaultDriver;
use Flarum\User\Avatar\DriverInterface as AvatarDriverInterface;
use Flarum\User\DisplayName\DriverInterface as DisplayNameDriverInterface;
use Flarum\User\DisplayName\UsernameDriver;
use Flarum\User\Event\EmailChangeRequested;
use Flarum\User\Event\Registered;
use Flarum\User\Event\Saving;
use Flarum\User\Throttler\EmailActivationThrottler;
use Flarum\User\Throttler\EmailChangeThrottler;
use Flarum\User\Throttler\PasswordResetThrottler;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Arr;

class UserServiceProvider extends AbstractServiceProvider
{
    public function register(): void
    {
        $this->registerDisplayNameDrivers();
        $this->registerAvatarDrivers();
        $this->registerPasswordCheckers();

        $this->container->singleton('flarum.user.group_processors', function () {
            return [];
        });

        $this->container->singleton('flarum.policies', function () {
            return [
                Access\AbstractPolicy::GLOBAL => [],
                AccessToken::class => [AccessTokenPolicy::class],
                Discussion::class => [DiscussionPolicy::class],
                Group::class => [GroupPolicy::class],
                Post::class => [PostPolicy::class],
                User::class => [Access\UserPolicy::class],
            ];
        });

        $this->container->extend('flarum.api.throttlers', function (array $throttlers, Container $container) {
            $throttlers['emailChangeTimeout'] = $container->make(EmailChangeThrottler::class);
            $throttlers['emailActivationTimeout'] = $container->make(EmailActivationThrottler::class);
            $throttlers['passwordResetTimeout'] = $container->make(PasswordResetThrottler::class);

            return $throttlers;
        });
    }

    protected function registerDisplayNameDrivers(): void
    {
        $this->container->singleton('flarum.user.display_name.supported_drivers', function () {
            return [
                'username' => UsernameDriver::class,
            ];
        });

        $this->container->singleton('flarum.user.display_name.driver', function (Container $container) {
            $drivers = $container->make('flarum.user.display_name.supported_drivers');
            $settings = $container->make(SettingsRepositoryInterface::class);
            $driverName = $settings->get('display_name_driver', '');

            $driverClass = Arr::get($drivers, $driverName);

            return $driverClass
                ? $container->make($driverClass)
                : $container->make(UsernameDriver::class);
        });

        $this->container->alias('flarum.user.display_name.driver', DisplayNameDriverInterface::class);
    }

    protected function registerAvatarDrivers(): void
    {
        $this->container->singleton('flarum.user.avatar.supported_drivers', function () {
            return [
                'default' => AvatarDefaultDriver::class,
            ];
        });

        $this->container->singleton('flarum.user.avatar.driver', function (Container $container) {
            $drivers = $container->make('flarum.user.avatar.supported_drivers');
            $settings = $container->make(SettingsRepositoryInterface::class);
            $driverName = $settings->get('avatar_driver', 'default');

            $driverClass = Arr::get($drivers, $driverName, AvatarDefaultDriver::class);

            return $container->make($driverClass);
        });

        $this->container->alias('flarum.user.avatar.driver', AvatarDriverInterface::class);
    }

    protected function registerPasswordCheckers(): void
    {
        $this->container->singleton('flarum.user.password_checkers', function (Container $container) {
            return [
                'standard' => function (User $user, #[\SensitiveParameter] $password) use ($container) {
                    if ($container->make('hash')->check($password, $user->password)) {
                        return true;
                    }
                }
            ];
        });
    }

    public function boot(Container $container, Dispatcher $events): void
    {
        foreach ($container->make('flarum.user.group_processors') as $callback) {
            User::addGroupProcessor(ContainerUtil::wrapCallback($callback, $container));
        }

        /**
         * @var \Illuminate\Container\Container $container
         */
        User::setHasher($container->make('hash'));
        User::setPasswordCheckers($container->make('flarum.user.password_checkers'));
        User::setGate($container->makeWith(Access\Gate::class, ['policyClasses' => $container->make('flarum.policies')]));
        User::setDisplayNameDriver($container->make('flarum.user.display_name.driver'));
        User::setAvatarDriver($container->make('flarum.user.avatar.driver'));

        $events->listen(Saving::class, SelfDemotionGuard::class);
        $events->listen(Registered::class, AccountActivationMailer::class);
        $events->listen(EmailChangeRequested::class, EmailConfirmationMailer::class);

        $events->subscribe(UserMetadataUpdater::class);
        $events->subscribe(TokensClearer::class);

        User::registerPreference('discloseOnline', 'boolval', true);
        User::registerPreference('indexProfile', 'boolval', true);
        User::registerPreference('locale');
        User::registerPreference('colorScheme', 'strval', 'auto');
        User::registerPreference('hapticFeedback', 'boolval', true);

        User::registerVisibilityScoper(new ScopeUserVisibility(), 'view');
    }
}
