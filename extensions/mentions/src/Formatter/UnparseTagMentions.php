<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Mentions\Formatter;

use Flarum\Database\AbstractModel;
use Flarum\Tags\Tag;
use s9e\TextFormatter\Utils;

class UnparseTagMentions
{
    public function __invoke(mixed $context, string $xml): string
    {
        return $this->unparseTagMentionTags(
            $this->updateTagMentionTags($context, $xml)
        );
    }

    /**
     * Updates XML user mention tags before unparsing so that unparsing uses new tag names.
     */
    protected function updateTagMentionTags(mixed $context, string $xml): string
    {
        return Utils::replaceAttributes($xml, 'TAGMENTION', function (array $attributes) use ($context) {
            /** @var Tag|null $tag */
            $tag = match (true) {
                $context instanceof AbstractModel && $context->isRelation('mentionsTags') => $context->relationLoaded('mentionsTags')
                    ? $context->mentionsTags->find($attributes['id']) // @phpstan-ignore-line
                    : $context->mentionsTags()->find($attributes['id']), // @phpstan-ignore-line
                default => Tag::query()->find($attributes['id']),
            };

            if ($tag) {
                $attributes['tagname'] = $tag->name;
                $attributes['slug'] = $tag->slug;
            }

            return $attributes;
        });
    }

    /**
     * Transforms tag mention tags from XML to raw unparsed content with updated name.
     */
    protected function unparseTagMentionTags(string $xml): string
    {
        $tagName = 'TAGMENTION';

        if (! str_contains($xml, $tagName)) {
            return $xml;
        }

        return preg_replace(
            '/<'.preg_quote($tagName).'\b[^>]*(?=\bid="([0-9]+)")[^>]*(?=\bslug="(.*)")[^>]*>@[^<]+<\/'.preg_quote($tagName).'>/U',
            '#$2',
            $xml
        );
    }
}
