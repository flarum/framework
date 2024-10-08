<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Mentions\Formatter;

use Flarum\Post\Post;
use s9e\TextFormatter\Utils;
use Symfony\Contracts\Translation\TranslatorInterface;

class UnparsePostMentions
{
    /**
     * @var TranslatorInterface
     */
    private $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * Configure rendering for user mentions.
     *
     * @param string|null $xml
     * @param mixed $context
     * @return mixed $xml to be unparsed
     */
    public function __invoke($context, $xml)
    {
        if ($xml === null) {
            return $xml;
        }

        $xml = $this->updatePostMentionTags($context, $xml);
        $xml = $this->unparsePostMentionTags($xml);

        return $xml;
    }

    /**
     * Updates XML post mention tags before unparsing so that unparsing uses new display names.
     *
     * @param mixed $context
     * @param string $xml : Parsed text.
     * @return string $xml : Updated XML tags;
     */
    protected function updatePostMentionTags($context, string $xml): string
    {
        $post = $context;

        return Utils::replaceAttributes($xml, 'POSTMENTION', function ($attributes) use ($context) {
            $post = (($context && isset($context->getRelations()['mentionsPosts'])) || $context instanceof Post)
                ? $context->mentionsPosts->find($attributes['id'])
                : Post::find($attributes['id']);

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
     *
     * @param string $xml : Parsed text.
     * @return string : Unparsed text.
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
