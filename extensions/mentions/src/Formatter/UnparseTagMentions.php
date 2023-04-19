<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Mentions\Formatter;

use Flarum\Post\Post;
use Flarum\Tags\Tag;
use s9e\TextFormatter\Utils;

class UnparseTagMentions
{
    /**
     * Configure rendering for user mentions.
     *
     * @param string $xml
     * @param mixed $context
     * @return string $xml to be unparsed
     */
    public function __invoke($context, string $xml)
    {
        $xml = $this->updateTagMentionTags($context, $xml);
        $xml = $this->unparseTagMentionTags($xml);

        return $xml;
    }

    /**
     * Updates XML user mention tags before unparsing so that unparsing uses new tag names.
     *
     * @param mixed $context
     * @param string $xml : Parsed text.
     * @return string $xml : Updated XML tags;
     */
    protected function updateTagMentionTags($context, string $xml): string
    {
        return Utils::replaceAttributes($xml, 'TAGMENTION', function (array $attributes) use ($context) {
            /** @var Tag|null $tag */
            $tag = (($context && isset($context->getRelations()['mentionsTags'])) || $context instanceof Post)
                ? $context->mentionsTags->find($attributes['id'])
                : Tag::query()->find($attributes['id']);

            if ($tag) {
                $attributes['tagname'] = $tag->name;
                $attributes['slug'] = $tag->slug;
            }

            return $attributes;
        });
    }

    /**
     * Transforms tag mention tags from XML to raw unparsed content with updated name.
     *
     * @param string $xml : Parsed text.
     * @return string : Unparsed text.
     */
    protected function unparseTagMentionTags(string $xml): string
    {
        $tagName = 'TAGMENTION';

        if (strpos($xml, $tagName) === false) {
            return $xml;
        }

        return preg_replace(
            '/<'.preg_quote($tagName).'\b[^>]*(?=\bid="([0-9]+)")[^>]*(?=\bslug="(.*)")[^>]*>@[^<]+<\/'.preg_quote($tagName).'>/U',
            '#$2',
            $xml
        );
    }
}
