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

use Flarum\Forum\Controller\DiscussionController as BaseDiscussionController;
use Psr\Http\Message\ServerRequestInterface;

class DiscussionController extends BaseDiscussionController
{
    /**
     * {@inheritdoc}
     */
    public function render(ServerRequestInterface $request)
    {
        $view = parent::render($request);

        $view->addBootstrapper('flarum/embed/main');
        $view->setLayout(__DIR__.'/../views/embed.blade.php');

        return $view;
    }

    /**
     * {@inheritdoc}
     */
    protected function getAssets()
    {
        $assets = parent::getAssets();

        $assets->addFile(__DIR__.'/../js/forum/dist/extension.js');
        $assets->addFile(__DIR__.'/../less/forum/extension.less');
        $assets->setFilename('embed');

        return $assets;
    }
}
