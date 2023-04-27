<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

use Flarum\Extend;
use s9e\TextFormatter\Configurator;
use s9e\TextFormatter\Renderer;

return [
    new Extend\Locales(__DIR__.'/locale'),

    (new Extend\Formatter)
        ->render(function (Renderer $renderer, $context, string $xml) {
            $renderer->setParameter('L_WROTE', resolve('translator')->trans('flarum-bbcode.forum.quote.wrote'));

            return $xml;
        })
        ->configure(function (Configurator $config) {
            $config->BBCodes->addFromRepository('B');
            $config->BBCodes->addFromRepository('I');
            $config->BBCodes->addFromRepository('U');
            $config->BBCodes->addFromRepository('S');
            $config->BBCodes->addFromRepository('URL');
            $config->BBCodes->addFromRepository('IMG');
            $config->BBCodes->addFromRepository('EMAIL');
            $config->BBCodes->addFromRepository('CODE');
            $config->BBCodes->addFromRepository('QUOTE', 'default', [
                'authorStr' => '<xsl:value-of select="@author"/> <xsl:value-of select="$L_WROTE"/>'
            ]);
            $config->BBCodes->addFromRepository('LIST');
            $config->BBCodes->addFromRepository('DEL');
            $config->BBCodes->addFromRepository('COLOR');
            $config->BBCodes->addFromRepository('CENTER');
            $config->BBCodes->addFromRepository('SIZE');
            $config->BBCodes->addFromRepository('*');
        }),
];
