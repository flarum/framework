<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Mentions\Formatter;

use Flarum\Database\AbstractModel;
use Flarum\Group\Group;
use Flarum\Locale\TranslatorInterface;
use s9e\TextFormatter\Renderer;
use s9e\TextFormatter\Utils;

class FormatGroupMentions
{
    public function __construct(
        private readonly TranslatorInterface $translator
    ) {
    }

    public function __invoke(Renderer $renderer, mixed $context, string $xml): string
    {
        return Utils::replaceAttributes($xml, 'GROUPMENTION', function ($attributes) use ($context) {
            /** @var Group|null $group */
            $group = match (true) {
                $context instanceof AbstractModel && $context->isRelation('mentionsGroups') => $context->relationLoaded('mentionsGroups')
                    ? $context->mentionsGroups->find($attributes['id']) // @phpstan-ignore-line
                    : $context->mentionsGroups()->find($attributes['id']), // @phpstan-ignore-line
                default => Group::query()->find($attributes['id']),
            };

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
