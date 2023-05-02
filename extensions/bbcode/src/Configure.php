<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\BBCode;

use s9e\TextFormatter\Configurator;

class Configure
{
    public function __invoke(Configurator $config)
    {
        $this->addTagsFromRepositories($config);
        $this->adaptHighlightJs($config);
    }

    protected function addTagsFromRepositories(Configurator $config): void
    {
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
    }

    /**
     * Fix for highlight JS not working after changing post content.
     *
     * @link https://github.com/flarum/framework/issues/3794
     */
    protected function adaptHighlightJs(Configurator $config): void
    {
        $codeTag = $config->tags->get('CODE');
        $script = '
                <script>
                    if(window.hljsLoader && !document.currentScript.parentNode.hasAttribute(\'data-s9e-livepreview-onupdate\')) {
                        window.hljsLoader.highlightBlocks(document.currentScript.parentNode);
                    }
                </script>';
        $codeTag->template = str_replace('</pre>', $script.'</pre>', $codeTag->template);
    }
}
