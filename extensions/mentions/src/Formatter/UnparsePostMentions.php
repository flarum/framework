<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Mentions\Formatter;

use s9e\TextFormatter\Utils;
use Symfony\Contracts\Translation\TranslatorInterface;

class UnparsePostMentions
{
    public function __construct(
        private readonly TranslatorInterface $translator
    ) {
    }

    public function __invoke(mixed $context, string $xml): string
    {
        return $this->unparsePostMentionTags(
            $this->updatePostMentionTags($context, $xml)
        );
    }

    /**
     * Updates XML post mention tags before unparsing so that unparsing uses new display names.
     */
    protected function updatePostMentionTags($context, string $xml): string
    {
        $post = $context;

        return Utils::replaceAttributes($xml, 'POSTMENTION', function ($attributes) use ($post) {
            $post = $post->mentionsPosts->find($attributes['id']);
            if ($post && $post->user) {
                $attributes['displayname'] = $post->user->display_name;
            }

            if (! $post) {
                $attributes['displayname'] = $this->translator->trans('flarum-mentions.forum.post_mention.deleted_text');
            }

            if ($post && ! $post->user) {
                $attributes['displayname'] = $this->translator->trans('core.lib.username.deleted_text');
            }

            if (strpos($attributes['displayname'], '"#') !== false) {
                $attributes['displayname'] = preg_replace('/"#[a-z]{0,3}[0-9]+/', '_', $attributes['displayname']);
            }

            return $attributes;
        });
    }

    /**
     * Transforms post mention tags from XML to raw unparsed content with updated format and display name.
     */
    protected function unparsePostMentionTags(string $xml): string
    {
        $tagName = 'POSTMENTION';

        if (strpos($xml, $tagName) === false) {
            return $xml;
        }

        return preg_replace(
            '/<'.preg_quote($tagName).'\b[^>]*(?=\bdisplayname="(.*)")[^>]*(?=\bid="([0-9]+)")[^>]*>@[^<]+<\/'.preg_quote($tagName).'>/U',
            '@"$1"#p$2',
            $xml
        );
    }
}
