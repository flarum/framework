<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Flarum\Extend;
use Flarum\Frontend\Document;
use Illuminate\Contracts\View\Factory;
use Psr\Http\Message\ServerRequestInterface as Request;

return [
    (new Extend\Frontend('forum'))
        ->route(
            '/embed/{id:\d+(?:-[^/]*)?}[/{near:[^/]*}]',
            'embed.discussion',
            function (Document $document, Request $request) {
                // Add the discussion content to the document so that the
                // payload will be included on the page and the JS app will be
                // able to render the discussion immediately.
                app(Flarum\Forum\Content\Discussion::class)($document, $request);

                app(Flarum\Frontend\Content\Assets::class)->forFrontend('embed')($document, $request);
            }
        ),

    (new Extend\Frontend('embed'))
        ->js(__DIR__.'/js/dist/forum.js')
        ->css(__DIR__.'/less/forum.less')
];
