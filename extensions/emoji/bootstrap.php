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
use s9e\TextFormatter\Configurator;

return [
    (new Extend\Assets('forum'))
        ->js(__DIR__.'/js/forum/dist/main.js')
        ->asset(__DIR__.'/less/forum/extension.less'),

    new Extend\FormatterConfiguration(function (Configurator $config) {
        $config->Emoji->useEmojiOne();
        $config->Emoji->omitImageSize();
        $config->Emoji->useSVG();

        $config->Emoji->addAlias(':)', '🙂');
        $config->Emoji->addAlias(':D', '😃');
        $config->Emoji->addAlias(':P', '😛');
        $config->Emoji->addAlias(':(', '🙁');
        $config->Emoji->addAlias(':|', '😐');
        $config->Emoji->addAlias(';)', '😉');
        $config->Emoji->addAlias(':\'(', '😢');
        $config->Emoji->addAlias(':O', '😮');
        $config->Emoji->addAlias('>:(', '😡');
    })
];
