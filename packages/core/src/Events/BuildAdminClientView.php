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

use Flarum\Support\ClientView;
use Flarum\Admin\Actions\ClientAction;

class BuildAdminClientView extends BuildClientView
{
    /**
     * @var ClientAction
     */
    public $action;

    /**
     * @param ClientAction $action
     * @param ClientView $view
     * @param array $keys
     */
    public function __construct(ClientAction $action, ClientView $view, array &$keys)
    {
        $this->action = $action;
        $this->view = $view;
        $this->keys = &$keys;
    }
}
