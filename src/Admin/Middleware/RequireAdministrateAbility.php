<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Admin\Middleware;

use Flarum\User\AssertPermissionTrait;
use Interop\Http\ServerMiddleware\DelegateInterface;
use Interop\Http\ServerMiddleware\MiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface as Request;

class RequireAdministrateAbility implements MiddlewareInterface
{
    use AssertPermissionTrait;

    public function process(Request $request, DelegateInterface $delegate)
    {
        $this->assertAdmin($request->getAttribute('actor'));

        return $delegate->process($request);
    }
}
