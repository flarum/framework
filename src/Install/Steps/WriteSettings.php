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

use Flarum\Foundation\Application;
use Flarum\Install\Step;
use Flarum\Settings\DatabaseSettingsRepository;
use Illuminate\Database\ConnectionInterface;

class WriteSettings implements Step
{
    /**
     * @var ConnectionInterface
     */
    private $database;

    /**
     * @var array
     */
    private $defaults;

    public function __construct(ConnectionInterface $database, array $defaults)
    {
        $this->database = $database;
        $this->defaults = $defaults;
    }

    public function getMessage()
    {
        return 'Writing default settings';
    }

    public function run()
    {
        $repo = new DatabaseSettingsRepository($this->database);

        $repo->set('version', Application::VERSION);

        foreach ($this->defaults as $key => $value) {
            $repo->set($key, $value);
        }
    }
}
