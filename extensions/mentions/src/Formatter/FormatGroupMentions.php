<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Mentions\Formatter;

use Flarum\Group\Group;
use Flarum\Post\Post;
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
     * @param \s9e\TextFormatter\Renderer $renderer
     * @param mixed $context
     * @param string $xml
     * @return string
     */
    public function __invoke(Renderer $renderer, $context, string $xml): string
    {
        return Utils::replaceAttributes($xml, 'GROUPMENTION', function ($attributes) use ($context) {
            $group = (($context && isset($context->getRelations()['mentionsGroups'])) || $context instanceof Post)
                ? $context->mentionsGroups->find($attributes['id'])
                : Group::find($attributes['id']);

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
