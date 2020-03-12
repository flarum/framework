<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Forum\Console;

use Flarum\Console\AbstractCommand;
use Flarum\Settings\SettingsRepositoryInterface;

class EnableUserPassAuthCommand extends AbstractCommand
{
    /**
     * @var SettingsRepositoryInterface
     */
    protected $settings;

    /**
     * @param SettingsRepositoryInterface $settings
     */
    public function __construct(SettingsRepositoryInterface $settings)
    {
        $this->settings = $settings;

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('auth:enableUserPassAuth')
            ->setDescription('Enable the standard username/password authentication system.');
    }

    /**
     * {@inheritdoc}
     */
    protected function fire()
    {
        $this->info('Reenabling...');

        $this->settings->set('enable_user_pass_auth', true);

        $this->info('DONE.');
    }
}
