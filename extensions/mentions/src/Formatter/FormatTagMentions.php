<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Mentions\Formatter;

use Flarum\Tags\Tag;
use Psr\Http\Message\ServerRequestInterface as Request;
use s9e\TextFormatter\Renderer;
use s9e\TextFormatter\Utils;

class FormatTagMentions
{
    use LooksUpMentionedModels;

    public function __invoke(Renderer $renderer, mixed $context, string $xml, ?Request $request = null): string
    {
        $tags = $this->mentionedModels($context, 'mentionsTags', $xml, 'TAGMENTION', Tag::query());

        return Utils::replaceAttributes($xml, 'TAGMENTION', function ($attributes) use ($tags) {
            /** @var Tag|null $tag */
            $tag = $tags->find($attributes['id']);

            if ($tag) {
                $attributes['deleted'] = false;
                $attributes['tagname'] = $tag->name;
                $attributes['slug'] = $tag->slug;
                $attributes['color'] = $tag->color ?? '';
                $attributes['icon'] = $tag->icon ?? '';
            } else {
                $attributes['deleted'] = true;
            }

            return $attributes;
        });
    }
}
