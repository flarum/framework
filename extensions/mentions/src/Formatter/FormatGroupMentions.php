<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Mentions\Formatter;

use Flarum\Group\Group;
use s9e\TextFormatter\Renderer;
use s9e\TextFormatter\Utils;
use Symfony\Contracts\Translation\TranslatorInterface;

class FormatGroupMentions
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
     * @param s9e\TextFormatter\Renderer $renderer
     * @param mixed $context
     * @param string|null $xml
     * @return void
     */
    public function __invoke(Renderer $renderer, $context, string $xml)
    {
        return Utils::replaceAttributes($xml, 'GROUPMENTION', function ($attributes) use ($context) {
            $group = (($context && isset($context->getRelations()['mentionsGroups'])) || $context instanceof Group)
            ? $context->mentionsGroups->find($attributes['id'])
            : Group::find($attributes['id']);

            if ($group) {
                $attributes['displayname'] = $group->name_plural;
                $attributes['icon'] = $group->icon ?? 'fas fa-at';
                $attributes['color'] = $group->color;
                $attributes['deleted'] = false;
            } else {
                $attributes['displayname'] = $this->translator->trans('flarum-mentions.forum.group_mention.deleted_text');
                $attributes['deleted'] = true;
            }

            return $attributes;
        });
    }
}