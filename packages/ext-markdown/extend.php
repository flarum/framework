<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

use Flarum\Extend;
use s9e\TextFormatter\Configurator;

return [
    (new Extend\Frontend('forum'))
        ->js(__DIR__.'/js/dist/forum.js')
        ->css(__DIR__.'/less/common.less'),

    (new Extend\Frontend('admin'))
        ->js(__DIR__.'/js/dist/admin.js')
        ->css(__DIR__.'/less/common.less'),

    (new Extend\Formatter)
        ->configure(function (Configurator $config) {
            $config->Litedown;

            // Overwrite the default inline spoiler so that it is compatible
            // with more styling for children in an external stylesheet.
            $config->tags['ispoiler']->template = '<span class="spoiler" data-s9e-livepreview-ignore-attrs="class" onclick="removeAttribute(\'class\')"><xsl:apply-templates/></span>';
        }),

    new Extend\Locales(__DIR__.'/locale'),
];
