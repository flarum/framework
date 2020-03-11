<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Forum\Auth;

use Psr\Http\Message\ServerRequestInterface as Request;

interface ExternalAuthDriverInterface
{
    public function sso(Request $request, SsoResponse $ssoResponse);
}