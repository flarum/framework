<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Post;

use DateTime;
use Flarum\Post\Event\CheckingForFlooding;
use Flarum\Post\Exception\FloodingException;
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\User\User;
use Illuminate\Contracts\Events\Dispatcher;

class Floodgate
{
    /**
     * @var Dispatcher
     */
    protected $events;

    /**
     * @var SettingsRepositoryInterface
     */
    protected $settings;

    public function __construct(Dispatcher $events, SettingsRepositoryInterface $settings)
    {
        $this->events = $events;
        $this->settings = $settings;
    }

    /**
     * @param User $actor
     * @throws FloodingException
     */
    public function assertNotFlooding(User $actor)
    {
        if ($actor->can('postWithoutThrottle')) {
            return;
        }

        if ($this->isFlooding($actor)) {
            throw new FloodingException;
        }
    }

    /**
     * @param User $actor
     * @return bool
     */
    public function isFlooding(User $actor): bool
    {
        $isFlooding = $this->events->until(
            new CheckingForFlooding($actor)
        );

        $floodtime = $this->settings->get('post_flood_interval', 15);

        return $isFlooding ??
            Post::where('user_id', $actor->id)
                ->where('created_at', '>=', new DateTime('-'.$floodtime.' seconds'))
                ->exists();
    }
}
