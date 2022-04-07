<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Mentions\Formatter;

use Flarum\Post\Post;
use Flarum\User\User;
use s9e\TextFormatter\Utils;
use Symfony\Contracts\Translation\TranslatorInterface;

class UnparseUserMentions
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
     * @param string $xml
     * @param mixed $context
     * @return string $xml to be unparsed
     */
    public function __invoke($context, string $xml)
    {
        $xml = $this->updateUserMentionTags($context, $xml);
        $xml = $this->unparseUserMentionTags($xml);

        return $xml;
    }

    /**
     * Updates XML user mention tags before unparsing so that unparsing uses new display names.
     *
     * @param mixed $context
     * @param string $xml : Parsed text.
     * @return string $xml : Updated XML tags;
     */
    protected function updateUserMentionTags($context, string $xml): string
    {
        return Utils::replaceAttributes($xml, 'USERMENTION', function ($attributes) use ($context) {
            $user = (($context && isset($context->getRelations()['mentionsUsers'])) || $context instanceof Post)
                ? $context->mentionsUsers->find($attributes['id'])
                : User::find($attributes['id']);

            if ($user) {
                $attributes['displayname'] = $user->display_name;
            } else {
                $attributes['displayname'] = $this->translator->trans('core.lib.username.deleted_text');
            }

            if (strpos($attributes['displayname'], '"#') !== false) {
                $attributes['displayname'] = preg_replace('/"#[a-z]{0,3}[0-9]+/', '_', $attributes['displayname']);
            }

            return $attributes;
        });
    }

    /**
     * Transforms user mention tags from XML to raw unparsed content with updated format and display name.
     *
     * @param string $xml : Parsed text.
     * @return string : Unparsed text.
     */
    protected function unparseUserMentionTags(string $xml): string
    {
        $tagName = 'USERMENTION';

        if (strpos($xml, $tagName) === false) {
            return $xml;
        }

        return preg_replace(
            '/<'.preg_quote($tagName).'\b[^>]*(?=\bdisplayname="(.*)")[^>]*(?=\bid="([0-9]+)")[^>]*>@[^<]+<\/'.preg_quote($tagName).'>/U',
            '@"$1"#$2',
            $xml
        );
    }
}
