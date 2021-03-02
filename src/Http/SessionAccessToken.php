<?php

namespace Flarum\Http;

class SessionAccessToken extends AccessToken
{
    public static $type = 'session';

    protected static $lifetime = 3600;
}
