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

use Flarum\Forum\WebApp;

class EmbedWebApp extends WebApp
{
    /**
     * {@inheritdoc}
     */
    public function getView()
    {
        $view = parent::getView();

        $view->getJs()->addFile(__DIR__.'/../js/forum/dist/extension.js');
        $view->getCss()->addFile(__DIR__.'/../less/forum/extension.less');

        $view->loadModule('flarum/embed/main');
        $view->setLayout(__DIR__.'/../views/embed.blade.php');

        return $view;
    }

    /**
     * {@inheritdoc}
     */
    public function getAssets()
    {
        return $this->assets->make('embed');
    }
}
