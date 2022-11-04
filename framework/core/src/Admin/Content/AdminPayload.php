<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Admin\Content;

use Flarum\Extension\ExtensionManager;
use Flarum\Foundation\Config;
use Flarum\Foundation\ScheduleRepository;
use Flarum\Frontend\Document;
use Flarum\Group\Permission;
use Flarum\Queue\QueueRepository;
use Flarum\Settings\Event\Deserializing;
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\User\User;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Queue\Queue;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Arr;
use Psr\Http\Message\ServerRequestInterface as Request;

class AdminPayload
{
    /**
     * @var Container;
     */
    protected $container;

    /**
     * @var SettingsRepositoryInterface
     */
    protected $settings;

    /**
     * @var ExtensionManager
     */
    protected $extensions;

    /**
     * @var ConnectionInterface
     */
    protected $db;

    /**
     * @var Dispatcher
     */
    protected $events;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var QueueRepository
     */
    protected $queues;

    /**
     * @var Queue
     */
    protected $queue;

    /**
     * @var ScheduleRepository
     */
    protected $schedules;

    /**
     * @param Container $container
     * @param SettingsRepositoryInterface $settings
     * @param ExtensionManager $extensions
     * @param ConnectionInterface $db
     * @param Dispatcher $events
     * @param Config $config
     * @param QueueRepository $queues
     * @param Queue $queue
     * @param ScheduleRepository $schedules
     */
    public function __construct(
        Container $container,
        SettingsRepositoryInterface $settings,
        ExtensionManager $extensions,
        ConnectionInterface $db,
        Dispatcher $events,
        Config $config,
        QueueRepository $queues,
        Queue $queue,
        ScheduleRepository $schedules
    ) {
        $this->container = $container;
        $this->settings = $settings;
        $this->extensions = $extensions;
        $this->db = $db;
        $this->events = $events;
        $this->config = $config;
        $this->queues = $queues;
        $this->queue = $queue;
        $this->schedules = $schedules;
    }

    public function __invoke(Document $document, Request $request)
    {
        $settings = $this->settings->all();

        $this->events->dispatch(
            new Deserializing($settings)
        );

        $document->payload['settings'] = $settings;
        $document->payload['permissions'] = Permission::map();
        $document->payload['extensions'] = $this->extensions->getExtensions()->toArray();

        $document->payload['displayNameDrivers'] = array_keys($this->container->make('flarum.user.display_name.supported_drivers'));
        $document->payload['slugDrivers'] = array_map(function ($resourceDrivers) {
            return array_keys($resourceDrivers);
        }, $this->container->make('flarum.http.slugDrivers'));

        $document->payload['phpVersion'] = PHP_VERSION;
        $document->payload['mysqlVersion'] = $this->db->selectOne('select version() as version')->version;
        $document->payload['debugEnabled'] = Arr::get($this->config, 'debug');

        if ($this->schedules->scheduledTasksRegistered()) {
            $document->payload['schedulerStatus'] = $this->schedules->getSchedulerStatus();
        }

        $document->payload['queueDriver'] = $this->queues->identifyDriver($this->queue);

        /**
         * Used in the admin user list. Implemented as this as it matches the API in flarum/statistics.
         * If flarum/statistics ext is enabled, it will override this data with its own stats.
         *
         * This allows the front-end code to be simpler and use one single source of truth to pull the
         * total user count from.
         */
        $document->payload['modelStatistics'] = [
            'users' => [
                'total' => User::count()
            ]
        ];
    }
}
