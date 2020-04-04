<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Http;

use Flarum\User\User;
use Illuminate\Contracts\Session\Session;
use Laminas\Diactoros\ServerRequest as BaseServerRequest;

class ServerRequest extends BaseServerRequest
{
    public function getActor()
    {
        return $this->getAttribute('actor');
    }

    public function withActor(User $actor)
    {
        return $this->withAttribute('actor', $actor);
    }

    public function getSession()
    {
        return $this->getAttribute('session');
    }

    public function withSession(Session $session)
    {
        return $this->withAttribute('session', $session);
    }

    public function getBypassFloodgate()
    {
        return $this->getAttribute('bypassFloodgate');
    }

    public function withBypassFloodgate(bool $bypassFloodgate)
    {
        return $this->withAttribute('bypassFloodgate', $bypassFloodgate);
    }

    public function getBypassCsrfToken()
    {
        return $this->getAttribute('bypassCsrfToken');
    }

    public function withBypassCsrfToken(bool $bypassCsrfToken)
    {
        return $this->withAttribute('bypassCsrfToken', $bypassCsrfToken);
    }

    public function getLocale()
    {
        return $this->getAttribute('bypassCsrfToken');
    }

    public function withLocale(string $locale)
    {
        return $this->withAttribute('locale', $locale);
    }
}
