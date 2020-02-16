<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Foundation\Console;

use Flarum\Console\AbstractCommand;
use Flarum\Install\StructureSkeleton;
use Symfony\Component\Console\Input\InputArgument;

class SkeletonCommand extends AbstractCommand {

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('skeleton:shared')
            ->setDescription('Optimize skeleton for shared hosting? (On or Off)')
            ->addArgument(
                'enable',
                InputArgument::REQUIRED,
                'Enable shared-hosting-optimized structure? (On or Off)'
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function fire()
    {
        if ($this->input->getArgument('enable') == 'On') {
            $out = StructureSkeleton::enableShared();
        } else if ($this->input->getArgument('enable') == 'Off') {
            $out = StructureSkeleton::disableShared();
        } else {
            $this->error("Invalid Option: Must be On or Off.");
        }

        if (isset($out)) {
            $this->output->writeln($out);
        }
    }
}