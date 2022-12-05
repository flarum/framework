<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tags\Formatter;

use Flarum\Extension\ExtensionManager;
use Flarum\Locale\Translator;
use Flarum\Post\Post;
use Flarum\Tags\Tag;
use s9e\TextFormatter\Renderer;
use s9e\TextFormatter\Utils;

class FormatTagMentions
{
    /**
     * @var ExtensionManager
     */
    private $extensions;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    public function __construct(ExtensionManager $extensions, Translator $translator)
    {
        $this->extensions = $extensions;
        $this->translator = $translator;
    }

    /**
     * Configure rendering for group mentions.
     *
     * @param \s9e\TextFormatter\Renderer $renderer
     * @param mixed $context
     * @param string $xml
     * @return string
     */
    public function __invoke(Renderer $renderer, $context, string $xml): string
    {
        if (! $this->extensions->isEnabled('flarum-mentions')) {
            return $xml;
        }

        return Utils::replaceAttributes($xml, 'TAGMENTION', function ($attributes) use ($context) {
            $tag = (($context && isset($context->getRelations()['mentionsTags'])) || $context instanceof Post)
            ? $context->mentionsTags->find($attributes['id'])
            : Tag::find($attributes['id']);

            if ($tag) {
                $attributes['tagname'] = $tag->name;
                $attributes['icon'] = $tag->icon ?? '';
                $attributes['color'] = $tag->color;
                $attributes['slug'] = $tag->slug;
                $attributes['deleted'] = false;
            } else {
                $attributes['tagname'] = $this->translator->trans('flarum-tags.lib.deleted_tag_text');
                $attributes['icon'] = '';
                $attributes['deleted'] = true;
            }

            return $attributes;
        });
    }
}
