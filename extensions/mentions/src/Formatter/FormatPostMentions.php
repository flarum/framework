<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Mentions\Formatter;

use Flarum\Discussion\Discussion;
use Flarum\Http\SlugManager;
use Flarum\Locale\TranslatorInterface;
use Flarum\Post\Post;
use Flarum\User\Guest;
use Illuminate\Database\Eloquent\Collection;
use Psr\Http\Message\ServerRequestInterface;
use s9e\TextFormatter\Renderer;
use s9e\TextFormatter\Utils;

class FormatPostMentions
{
    use LooksUpMentionedModels;

    public function __construct(
        private readonly TranslatorInterface $translator,
        private readonly SlugManager $slugManager
    ) {
    }

    /**
     * Configure rendering for post mentions.
     */
    public function __invoke(Renderer $renderer, mixed $context, string $xml, ?ServerRequestInterface $request = null): string
    {
        $actor = $request?->getAttribute('actorReference')?->getActor();

        // A mention links to its post's discussion by slug, which carries the
        // discussion's title: posts the actor cannot see render as deleted.
        $posts = $this->mentionedModels($context, 'mentionsPosts', $xml, 'POSTMENTION', fn () => Post::query()
            ->whereVisibleTo($actor ?? new Guest())
            ->with(['user', 'discussion']));

        // Renders without an actor, such as emails, only name what guests can
        // see. As core does for links to discussions, the rest keep their link,
        // by the discussion's ID alone.
        $discussionIdsOfPostsHiddenFromGuests = $actor ? [] : $this->discussionIdsOfPostsMissingFrom($posts, $xml);

        return Utils::replaceAttributes($xml, 'POSTMENTION', function ($attributes) use ($posts, $discussionIdsOfPostsHiddenFromGuests) {
            /** @var Post|null $post */
            $post = $posts->find($attributes['id']);

            if (! $post && isset($discussionIdsOfPostsHiddenFromGuests[$attributes['id']])) {
                $attributes['discussionid'] = $discussionIdsOfPostsHiddenFromGuests[$attributes['id']];
                $attributes['deleted'] = false;

                return $attributes;
            }

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

    /**
     * @param Collection<int, Post> $posts
     * @return array<int|string, int> the discussion ID of each mentioned post that exists but is not in $posts
     */
    private function discussionIdsOfPostsMissingFrom(Collection $posts, string $xml): array
    {
        $missing = array_diff(Utils::getAttributeValues($xml, 'POSTMENTION', 'id'), $posts->modelKeys());

        return $missing ? Post::query()->whereIn('id', array_unique($missing))->pluck('discussion_id', 'id')->all() : [];
    }
}
