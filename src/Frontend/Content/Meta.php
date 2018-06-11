<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Frontend\Content;

use Flarum\Frontend\FrontendView;
use Psr\Http\Message\ServerRequestInterface as Request;

class Meta implements ContentInterface
{
    public function populate(FrontendView $view, Request $request)
    {
        $view->meta = array_merge($view->meta, $this->buildMeta($view));
        $view->head = array_merge($view->head, $this->buildHead($view));
    }

    private function buildMeta(FrontendView $view)
    {
        $forumDocument = $view->getForumDocument();

        $meta = [
            'viewport' => 'width=device-width, initial-scale=1, maximum-scale=1, minimum-scale=1',
            'description' => array_get($forumDocument, 'data.attributes.forumDescription'),
            'theme-color' => array_get($forumDocument, 'data.attributes.themePrimaryColor')
        ];

        return $meta;
    }

    private function buildHead(FrontendView $view)
    {
        $head = [
            'font' => '<link rel="stylesheet" href="//fonts.googleapis.com/css?family=Open+Sans:400italic,700italic,400,700,600">'
        ];

        if ($faviconUrl = array_get($view->getForumDocument(), 'data.attributes.faviconUrl')) {
            $head['favicon'] = '<link rel="shortcut icon" href="'.e($faviconUrl).'">';
        }

        return $head;
    }
}
