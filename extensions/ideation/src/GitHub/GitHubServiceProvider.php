<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Ideation\GitHub;

use Flarum\Foundation\AbstractServiceProvider;

class GitHubServiceProvider extends AbstractServiceProvider
{
    public function register(): void
    {
        $this->container->bindIf(Client::class, HttpClient::class);
    }
}
