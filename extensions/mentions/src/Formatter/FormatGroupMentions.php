<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Mentions\Formatter;

use Flarum\Group\Group;
use Flarum\Locale\TranslatorInterface;
use s9e\TextFormatter\Renderer;
use s9e\TextFormatter\Utils;

class FormatGroupMentions
{
    use LooksUpMentionedModels;

    public function __construct(
        private readonly TranslatorInterface $translator
    ) {
    }

    public function __invoke(Renderer $renderer, mixed $context, string $xml): string
    {
        $groups = $this->mentionedModels($context, 'mentionsGroups', $xml, 'GROUPMENTION', Group::query());

        return Utils::replaceAttributes($xml, 'GROUPMENTION', function ($attributes) use ($groups) {
            /** @var Group|null $group */
            $group = $groups->find($attributes['id']);

            if ($group) {
                $attributes['groupname'] = $group->name_plural;
                $attributes['icon'] = $group->icon ?? 'fas fa-at';
                $attributes['color'] = $group->color;
                $attributes['deleted'] = false;
            } else {
                $attributes['groupname'] = $this->translator->trans('flarum-mentions.forum.group_mention.deleted_text');
                $attributes['icon'] = '';
                $attributes['deleted'] = true;
            }

            return $attributes;
        });
    }
}
