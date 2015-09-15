<?php
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Api\Actions\Extensions;

use Flarum\Api\Actions\Action;
use Flarum\Api\Request;
use Illuminate\Contracts\Bus\Dispatcher;
use Flarum\Core\Exceptions\PermissionDeniedException;
use Flarum\Support\ExtensionManager;

class UpdateAction implements Action
{
    protected $extensions;

    public function __construct(ExtensionManager $extensions)
    {
        $this->extensions = $extensions;
    }

    public function handle(Request $request)
    {
        if (! $request->actor->isAdmin()) {
            throw new PermissionDeniedException;
        }

        $enabled = $request->get('enabled');
        $name = $request->get('name');

        if ($enabled === true) {
            $this->extensions->enable($name);
        } elseif ($enabled === false) {
            $this->extensions->disable($name);
        }

        app('flarum.formatter')->flush();

        $forum = app('Flarum\Forum\Actions\ClientAction');
        $forum->flushAssets();

        $admin = app('Flarum\Admin\Actions\ClientAction');
        $admin->flushAssets();
    }
}
