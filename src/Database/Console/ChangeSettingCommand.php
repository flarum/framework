<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Database\Console;

use Flarum\Console\AbstractCommand;
use Flarum\Settings\SettingsRepositoryInterface;
use Symfony\Component\Console\Input\InputArgument;

class ChangeSettingCommand extends AbstractCommand
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
            ->setName('setting:change')
            ->setDescription('Change a setting programatically')
            ->addArgument(
                'setting',
                InputArgument::REQUIRED,
                'The name of the setting to change.'
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function fire()
    {
        $setting = $this->input->getArgument('setting');
        $this->info('Configuring setting: '.$setting);

        $newValue = $this->askForSettingValue();
        $this->settings->set($setting, $newValue);

        $this->info('Done');
    }

    protected function askForSettingValue()
    {
        while (true) {
            $value = $this->ask('New Value:');

            json_decode($value);

            if (json_last_error() === JSON_ERROR_NONE) {
                break;
            } else {
                $this->error('Value must be a valid json string.');
            }
        }
    }
}
