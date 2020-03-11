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
    /**
     * If, while logging in,  an email returned by this driver matches that of an existing user, should:
     * - this driver be added as a login provider to the user, or
     * - an error message be shown that a user with this email already exists, and
     *   the user be asked to try again, or to add this provider from their settings page?
     */
    public function trustEmails(): bool;

    public function sso(Request $request): SsoResponse;
}