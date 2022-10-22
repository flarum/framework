<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Mentions\Formatter;

use Symfony\Contracts\Translation\TranslatorInterface;

class UnparseGroupMentions
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
     * Configure rendering for group mentions.
     *
     * @param string $xml
     * @param mixed $context
     * @return string $xml to be unparsed
     */
    public function __invoke($context, string $xml)
    {
        $xml = $this->unparseGroupMentionTags($xml);

        return $xml;
    }

    /**
     * Transforms group mention tags from XML to raw unparsed content with updated format and display name.
     *
     * @param string $xml : Parsed text.
     * @return string : Unparsed text.
     */
    protected function unparseGroupMentionTags(string $xml): string
    {
        $tagName = 'GROUPMENTION';

        if (strpos($xml, $tagName) === false) {
            return $xml;
        }

        return preg_replace(
            '/<'.preg_quote($tagName).'\b[^>]*(?=\bgroupname="(.*)")[^>]*(?=\bid="([0-9]+)")[^>]*>@[^<]+<\/'.preg_quote($tagName).'>/U',
            '@"$1"#g$2',
            $xml
        );
    }
}
