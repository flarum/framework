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

use Flarum\Forum\Actions\DiscussionAction as DiscussionAction;
use Psr\Http\Message\ServerRequestInterface as Request;

class ClientAction extends DiscussionAction
{
    /**
     * {@inheritdoc}
     *
     * @return ClientView
     */
    public function render(Request $request, array $routeParams = [])
    {
        $view = parent::render($request, $routeParams);

        $view->addBootstrapper('embed/main');
        $view->setLayout(__DIR__.'/../views/embed.blade.php');

        return $view;
    }

    /**
     * @inheritdoc
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
