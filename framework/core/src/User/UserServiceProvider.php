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
use Flarum\User\DisplayName\DriverInterface;
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
    /**
     * {@inheritdoc}
     */
    public function register()
    {
        $this->registerDisplayNameDrivers();
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

    protected function registerDisplayNameDrivers()
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

        $this->container->alias('flarum.user.display_name.driver', DriverInterface::class);
    }

    protected function registerPasswordCheckers()
    {
        $this->container->singleton('flarum.user.password_checkers', function (Container $container) {
            return [
                'standard' => function (User $user, $password) use ($container) {
                    if ($container->make('hash')->check($password, $user->password)) {
                        return true;
                    }
                }
            ];
        });
    }

    /**
     * {@inheritdoc}
     */
    public function boot(Container $container, Dispatcher $events)
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

        $events->listen(Saving::class, SelfDemotionGuard::class);
        $events->listen(Registered::class, AccountActivationMailer::class);
        $events->listen(EmailChangeRequested::class, EmailConfirmationMailer::class);

        $events->subscribe(UserMetadataUpdater::class);
        $events->subscribe(TokensClearer::class);

        User::registerPreference('discloseOnline', 'boolval', true);
        User::registerPreference('indexProfile', 'boolval', true);
        User::registerPreference('locale');

        User::registerVisibilityScoper(new ScopeUserVisibility(), 'view');
    }
}
