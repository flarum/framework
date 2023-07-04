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
use Flarum\Post\CommentPost;
use Psr\Http\Message\ServerRequestInterface as Request;
use s9e\TextFormatter\Renderer;
use s9e\TextFormatter\Utils;
use Symfony\Contracts\Translation\TranslatorInterface;

class FormatPostMentions
{
    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var SlugManager
     */
    private $slugManager;

    public function __construct(TranslatorInterface $translator, SlugManager $slugManager)
    {
        $this->translator = $translator;
        $this->slugManager = $slugManager;
    }

    /**
     * Configure rendering for post mentions.
     *
     * @param \s9e\TextFormatter\Renderer $renderer
     * @param mixed $context
     * @param string|null $xml
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @return string
     */
    public function __invoke(Renderer $renderer, $context, $xml, Request $request = null)
    {
        $post = $context;

        return Utils::replaceAttributes($xml, 'POSTMENTION', function ($attributes) use ($post) {
            $post = $post->mentionsPosts->find($attributes['id']);
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
