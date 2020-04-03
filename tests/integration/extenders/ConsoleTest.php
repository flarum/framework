<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\extenders;

use Flarum\Console\AbstractCommand;
use Flarum\Extend;
use Flarum\Tests\integration\ConsoleTestCase;

class ConsoleTest extends ConsoleTestCase
{
    /**
     * @test
     */
    public function custom_command_doesnt_exist_by_default()
    {
        $input = [
            'command' => 'customTestCommand'
        ];

        $this->assertEquals('Command "customTestCommand" is not defined.', $this->runCommand($input));
    }

    /**
     * @test
     */
    public function custom_command_exists_when_added()
    {
        $this->extend(
            (new Extend\Console())
                ->command(CustomCommand::class)
        );

        $input = [
            'command' => 'customTestCommand'
        ];

        $this->assertEquals('Custom Command.', $this->runCommand($input));
    }
}

class CustomCommand extends AbstractCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('customTestCommand');
    }

    /**
     * {@inheritdoc}
     */
    protected function fire()
    {
        $this->info('Custom Command.');
    }
}
