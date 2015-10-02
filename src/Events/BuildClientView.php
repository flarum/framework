<?php
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Events;

use Flarum\Support\ClientAction;
use Flarum\Support\ClientView;

abstract class BuildClientView
{
    /**
     * @var ClientAction
     */
    public $action;

    /**
     * @var ClientView
     */
    public $view;

    /**
     * @var array
     */
    public $keys;

    public function assets($files)
    {
        $this->view->getAssets()->addFiles((array) $files);
    }

    public function bootstrapper($bootstrapper)
    {
        $this->view->addBootstrapper($bootstrapper);
    }

    public function translations(array $keys)
    {
        foreach ($keys as $key) {
            $this->keys[] = $key;
        }
    }
}
