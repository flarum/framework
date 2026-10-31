<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Extend;

use Flarum\Extension\Extension;
use Flarum\Http\AccessToken as AccessTokenModel;
use Illuminate\Contracts\Container\Container;

class AccessToken implements ExtenderInterface
{
    private array $types = [];

    /**
     * Add a type of access token.
     *
     * The class decides what its type is called, how long it lasts by default,
     * and whether a site may change that — see `Flarum\Http\AccessToken`.
     * Registering it here is what lets rows of that type be hydrated as the
     * right class, and what puts its lifetime in front of an administrator
     * alongside the types core ships.
     *
     * @param class-string<AccessTokenModel> $type: The ::class attribute of the token class,
     *                                              which must extend \Flarum\Http\AccessToken.
     */
    public function type(string $type): self
    {
        $this->types[] = $type;

        return $this;
    }

    public function extend(Container $container, ?Extension $extension = null): void
    {
        $container->extend('flarum.http.access_token_types', function (array $existing) {
            return array_merge($existing, $this->types);
        });
    }
}
