<?php

namespace Flarum\BBCode;

use s9e\TextFormatter\Renderer;
use Symfony\Contracts\Translation\TranslatorInterface;

class Render
{
    /**
     * @var TranslatorInterface
     */
    protected $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    public function __invoke(Renderer $renderer, $context, string $xml): string
    {
        $renderer->setParameter('L_WROTE', $this->translator->trans('flarum-bbcode.forum.quote.wrote'));

        return $xml;
    }
}
