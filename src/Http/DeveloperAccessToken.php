<?php

namespace Flarum\Http;

class DeveloperAccessToken extends AccessToken
{
    public static $type = 'developer';

    protected static $lifetime = 0;
}
