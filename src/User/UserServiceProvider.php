<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\User;

use Flarum\Discussion\DiscussionPolicy;
use Flarum\Event\ConfigureUserPreferences;
use Flarum\Event\GetPermission;
use Flarum\Event\ScopeModelVisibility;
use Flarum\Foundation\AbstractServiceProvider;
use Flarum\Group\GroupPolicy;
use Flarum\Post\PostPolicy;
use Flarum\User\Event\EmailChangeRequested;
use Flarum\User\Event\Registered;
use Flarum\User\Event\Saving;
use Illuminate\Contracts\Auth\Access\Gate as GateContract;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Filesystem\Factory;
use League\Flysystem\FilesystemInterface;
use RuntimeException;

class UserServiceProvider extends AbstractServiceProvider
{
    /**
     * {@inheritdoc}
     */
    public function register()
    {
        $this->registerGate();
        $this->registerAvatarsFilesystem();

        $this->app->singleton('flarum.policies', function () {
            return [
                DiscussionPolicy::class,
                GroupPolicy::class,
                PostPolicy::class,
                UserPolicy::class,
            ];
        });
    }

    protected function registerGate()
    {
        $this->app->singleton('flarum.gate', function ($app) {
            return new Gate($app, function () {
                throw new RuntimeException('You must set the gate user with forUser()');
            });
        });

        $this->app->alias('flarum.gate', GateContract::class);
        $this->app->alias('flarum.gate', Gate::class);
    }

    protected function registerAvatarsFilesystem()
    {
        $avatarsFilesystem = function (Container $app) {
            return $app->make(Factory::class)->disk('flarum-avatars')->getDriver();
        };

        $this->app->when(AvatarUploader::class)
            ->needs(FilesystemInterface::class)
            ->give($avatarsFilesystem);
    }

    /**
     * {@inheritdoc}
     */
    public function boot()
    {
        $this->app->make('flarum.gate')->before(function (User $actor, $ability, $model = null) {
            $evaluationCriteria = [
                AbstractPolicy::$FORCE_DENY => false,
                AbstractPolicy::$FORCE_ALLOW => true,
                AbstractPolicy::$DENY => false,
                AbstractPolicy::$ALLOW => true,
            ];

            $results = [];
            foreach ($this->app->make('flarum.policies') as $policy) {
                $results[] = $this->app->make($policy)->checkAbility($actor, $ability, $model);
            }

            foreach ($evaluationCriteria as $criteria => $decision) {
                if (in_array($criteria, $results, true)) {
                    return $decision;
                }
            }

            // START OLD DEPRECATED SYSTEM

            // Fire an event so that core and extension policies can hook into
            // this permission query and explicitly grant or deny the
            // permission.
            $allowed = $this->app->make('events')->until(
                new GetPermission($actor, $ability, $model)
            );

            if (! is_null($allowed)) {
                return $allowed;
            }

            // END OLD DEPRECATED SYSTEM

            // If no policy covered this permission query, we will only grant
            // the permission if the actor's groups have it. Otherwise, we will
            // not allow the user to perform this action.
            if ($actor->isAdmin() || (! $model && $actor->hasPermission($ability))) {
                return true;
            }

            return false;
        });

        User::setHasher($this->app->make('hash'));
        User::setGate($this->app->make('flarum.gate'));

        $events = $this->app->make('events');

        $events->listen(Saving::class, SelfDemotionGuard::class);
        $events->listen(Registered::class, AccountActivationMailer::class);
        $events->listen(EmailChangeRequested::class, EmailConfirmationMailer::class);

        $events->subscribe(UserMetadataUpdater::class);

        $events->listen(ConfigureUserPreferences::class, [$this, 'configureUserPreferences']);

        foreach ($this->app->make('flarum.policies') as $policy) {
            $events->listen(ScopeModelVisibility::class, [$this->app->make($policy), 'scopeQueryListener']);
        }
    }

    /**
     * @param ConfigureUserPreferences $event
     */
    public function configureUserPreferences(ConfigureUserPreferences $event)
    {
        $event->add('discloseOnline', 'boolval', true);
        $event->add('indexProfile', 'boolval', true);
        $event->add('locale');
    }
}
