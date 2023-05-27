<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\BBCode;

use Flarum\Locale\TranslatorInterface;
use s9e\TextFormatter\Renderer;

class Render
{
    public function __construct(
        protected TranslatorInterface $translator
    ) {
    }

    public function __invoke(Renderer $renderer, $context, string $xml): string
    {
        $renderer->setParameter('L_WROTE', $this->translator->trans('flarum-bbcode.forum.quote.wrote'));

        return $xml;
    }
}
