<?php
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Forum\Controller;

use Flarum\Core\User;
use Psr\Http\Message\ServerRequestInterface as Request;
use Flarum\Core\Exception\PermissionDeniedException;

class AuthorizedClientController extends ClientController
{
    /**
     * {@inheritdoc}
     */
    public function render(Request $request)
    {
        if (!$request->getAttribute('session')->get('user_id')) {
            throw new PermissionDeniedException;
        }

        return parent::render($request);
    }
}
