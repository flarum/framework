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
    new Extend\Locales(__DIR__.'/locale'),

    (new Extend\Formatter)
        ->configure(function (Configurator $config) {
            $config->BBCodes->addFromRepository('B');
            $config->BBCodes->addFromRepository('I');
            $config->BBCodes->addFromRepository('U');
            $config->BBCodes->addFromRepository('S');
            $config->BBCodes->addFromRepository('URL');
            $config->BBCodes->addFromRepository('IMG');
            $config->BBCodes->addFromRepository('EMAIL');
            $config->BBCodes->addFromRepository('CODE');
            $config->BBCodes->addFromRepository('LIST');
            $config->BBCodes->addFromRepository('DEL');
            $config->BBCodes->addFromRepository('COLOR');
            $config->BBCodes->addFromRepository('CENTER');
            $config->BBCodes->addFromRepository('SIZE');
            $config->BBCodes->addFromRepository('*');

            // Quote translation
            $config->BBCodes->addFromRepository('QUOTE');
            $config->rendering->parameters['L_WROTE'] = resolve('translator')->trans('flarum-bbcode.forum.quote.wrote');
            $tag = $config->tags['QUOTE'];
            $tag->template = '<blockquote><xsl:if test="not(@author)"><xsl:attribute name="class">uncited</xsl:attribute></xsl:if><div><xsl:if test="@author"><cite><xsl:value-of select="@author"/> <xsl:value-of select="$L_WROTE"/></cite></xsl:if><xsl:apply-templates/></div></blockquote>';
        }),
];
