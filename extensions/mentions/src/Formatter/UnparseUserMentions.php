<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Mentions\Formatter;

use Flarum\Locale\TranslatorInterface;
use Flarum\Post\Post;
use Flarum\User\User;
use s9e\TextFormatter\Utils;

class UnparseUserMentions
{
    public function __construct(
        private readonly TranslatorInterface $translator
    ) {
    }

    public function __invoke(mixed $context, string $xml): string
    {
        return $this->unparseUserMentionTags(
            $this->updateUserMentionTags($context, $xml)
        );
    }

    /**
     * Updates XML user mention tags before unparsing so that unparsing uses new display names.
     */
    protected function updateUserMentionTags(mixed $context, string $xml): string
    {
        return Utils::replaceAttributes($xml, 'USERMENTION', function ($attributes) use ($context) {
            $user = (($context && isset($context->getRelations()['mentionsUsers'])) || $context instanceof Post)
                ? $context->mentionsUsers->find($attributes['id'])
                : User::find($attributes['id']);

            $attributes['displayname'] = $user?->display_name ?? $this->translator->trans('core.lib.username.deleted_text');

            if (str_contains($attributes['displayname'], '"#')) {
                $attributes['displayname'] = preg_replace('/"#[a-z]{0,3}[0-9]+/', '_', $attributes['displayname']);
            }

            return $attributes;
        });
    }

    /**
     * Transforms user mention tags from XML to raw unparsed content with updated format and display name.
     */
    protected function unparseUserMentionTags(string $xml): string
    {
        $tagName = 'USERMENTION';

        if (! str_contains($xml, $tagName)) {
            return $xml;
        }

        return preg_replace(
            '/<'.preg_quote($tagName).'\b[^>]*(?=\bdisplayname="(.*)")[^>]*(?=\bid="([0-9]+)")[^>]*>@[^<]+<\/'.preg_quote($tagName).'>/U',
            '@"$1"#$2',
            $xml
        );
    }
}
