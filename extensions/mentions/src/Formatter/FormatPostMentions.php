<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Mentions\Formatter;

use Flarum\Database\AbstractModel;
use Flarum\Discussion\Discussion;
use Flarum\Http\SlugManager;
use Flarum\Locale\TranslatorInterface;
use Flarum\Post\Post;
use s9e\TextFormatter\Renderer;
use s9e\TextFormatter\Utils;

class FormatPostMentions
{
    public function __construct(
        private readonly TranslatorInterface $translator,
        private readonly SlugManager $slugManager
    ) {
    }

    /**
     * Configure rendering for post mentions.
     */
    public function __invoke(Renderer $renderer, mixed $context, string $xml): string
    {
        return Utils::replaceAttributes($xml, 'POSTMENTION', function ($attributes) use ($context) {
            /** @var Post|null $post */
            $post = match (true) {
                $context instanceof AbstractModel && $context->isRelation('mentionsPosts') => $context->relationLoaded('mentionsPosts')
                    ? $context->mentionsPosts->find($attributes['id']) // @phpstan-ignore-line
                    : $context->mentionsPosts()->find($attributes['id']), // @phpstan-ignore-line
                default => Post::query()->find($attributes['id']),
            };

            if ($post && $post->user) {
                $attributes['displayname'] = $post->user->display_name;
            }

            $attributes['deleted'] = false;

            if (! $post) {
                $attributes['displayname'] = $this->translator->trans('flarum-mentions.forum.post_mention.deleted_text');
                $attributes['deleted'] = true;
            }

            if ($post && ! $post->user) {
                $attributes['displayname'] = $this->translator->trans('core.lib.username.deleted_text');
            }

            if ($post) {
                $attributes['discussionid'] = $this->slugManager
                    ->forResource(Discussion::class)
                    ->toSlug($post->discussion);
            }

            return $attributes;
        });
    }
}
