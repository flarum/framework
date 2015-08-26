<?php
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Api\Actions\Users;

use Flarum\Core\Users\Commands\DeleteUser;
use Flarum\Api\Actions\DeleteAction as BaseDeleteAction;
use Flarum\Api\Request;
use Illuminate\Contracts\Bus\Dispatcher;

class DeleteAction extends BaseDeleteAction
{
    /**
     * @var Dispatcher
     */
    protected $bus;

    /**
     * @param Dispatcher $bus
     */
    public function __construct(Dispatcher $bus)
    {
        $this->bus = $bus;
    }

    /**
     * Delete a user.
     *
     * @param Request $request
     */
    protected function delete(Request $request)
    {
        $this->bus->dispatch(
            new DeleteUser($request->get('id'), $request->actor)
        );
    }
}
