<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Http;

use Flarum\Admin\Http\UrlGeneratorInterface as AdminInterface;
use Flarum\Api\Http\UrlGeneratorInterface as ApiInterface;
use Flarum\Forum\Http\UrlGeneratorInterface as ForumInterface;

interface UrlGeneratorInterface extends AdminInterface, ApiInterface, ForumInterface
{
    public function toRoute($name, $parameters = []);

    public function toAsset($path);
}
