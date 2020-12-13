<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Mentions\Formatter;

use Psr\Http\Message\ServerRequestInterface as Request;
use s9e\TextFormatter\Renderer;
use s9e\TextFormatter\Utils;

class FormatUserMentions
{
    /**
     * Configure rendering for user mentions.
     *
     * @param s9e\TextFormatter\Renderer $renderer
     * @param mixed $context
     * @param string|null $xml
     * @param Psr\Http\Message\ServerRequestInterface $request
     */
    public function __invoke(Renderer $renderer, $context, $xml, Request $request = null)
    {
        $post = $context;

        return Utils::replaceAttributes($xml, 'USERMENTION', function ($attributes) use ($post) {
            $user = $post->mentionsUsers->find($attributes['id']);
            if ($user) {
                $attributes['username'] = $user->username;
                $attributes['displayname'] = $user->display_name;
            }

            return $attributes;
        });
    }
}
