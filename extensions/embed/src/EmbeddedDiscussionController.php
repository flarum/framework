<?php
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Embed;

use Flarum\Api\Client;
use Flarum\Forum\Controller\DiscussionController;
use Flarum\Forum\UrlGenerator;
use Illuminate\Contracts\Events\Dispatcher;

class EmbeddedDiscussionController extends DiscussionController
{
    /**
     * {@inheritdoc}
     */
    public function __construct(EmbedWebApp $webApp, Dispatcher $events, Client $api, UrlGenerator $url)
    {
        parent::__construct($webApp, $events, $api, $url);
    }
}
