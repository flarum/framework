<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Install\Steps;

use Flarum\Install\ReversibleStep;
use Flarum\Install\Step;

class StoreConfig implements Step, ReversibleStep
{
    private $config;

    private $configFile;

    public function __construct(array $config, $configFile)
    {
        $this->config = $config;
        $this->configFile = $configFile;
    }

    public function getMessage()
    {
        return 'Writing config file';
    }

    public function run()
    {
        file_put_contents(
            $this->configFile,
            '<?php return '.var_export($this->config, true).';'
        );
    }

    public function revert()
    {
        @unlink($this->configFile);
    }
}
