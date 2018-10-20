<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Illuminate\Database\Schema\Builder;

return [
    'up' => function (Builder $schema) {
        $emojioneToTwemojiMap = [
            '1f468-2764-1f468' => '1f468-200d-2764-fe0f-200d-1f468',
            '1f469-2764-1f469' => '1f469-200d-2764-fe0f-200d-1f469',
            '1f468-2764-1f48b-1f468' => '1f468-200d-2764-fe0f-200d-1f48b-200d-1f468',
            '1f469-2764-1f48b-1f469' => '1f469-200d-2764-fe0f-200d-1f48b-200d-1f469',
            '1f468-1f468-1f466' => '1f468-200d-1f468-200d-1f466',
            '1f468-1f468-1f466-1f466' => '1f468-200d-1f468-200d-1f466-200d-1f466',
            '1f468-1f468-1f467' => '1f468-200d-1f468-200d-1f467',
            '1f468-1f468-1f467-1f466' => '1f468-200d-1f468-200d-1f467-200d-1f466',
            '1f468-1f468-1f467-1f467' => '1f468-200d-1f468-200d-1f467-200d-1f467',
            '1f468-1f469-1f466-1f466' => '1f468-200d-1f469-200d-1f466-200d-1f466',
            '1f468-1f469-1f467' => '1f468-200d-1f469-200d-1f467',
            '1f468-1f469-1f467-1f466' => '1f468-200d-1f469-200d-1f467-200d-1f466',
            '1f468-1f469-1f467-1f467' => '1f468-200d-1f469-200d-1f467-200d-1f467',
            '1f469-1f469-1f466' => '1f469-200d-1f469-200d-1f466',
            '1f469-1f469-1f466-1f466' => '1f469-200d-1f469-200d-1f466-200d-1f466',
            '1f469-1f469-1f467' => '1f469-200d-1f469-200d-1f467',
            '1f469-1f469-1f467-1f466' => '1f469-200d-1f469-200d-1f467-200d-1f466',
            '1f469-1f469-1f467-1f467' => '1f469-200d-1f469-200d-1f467-200d-1f467',
            '1f441-1f5e8' => '1f441-200d-1f5e8', // as always PITA
            '1f3f3-1f308' => '1f3f3-fe0f-200d-1f308',

            // https://github.com/twitter/twemoji/issues/192
            '1f91d-1f3fb' => '1f91d',
            '1f91d-1f3fc' => '1f91d',
            '1f91d-1f3fd' => '1f91d',
            '1f91d-1f3fe' => '1f91d',
            '1f91d-1f3ff' => '1f91d',
            '1f93c-1f3fb' => '1f93c',
            '1f93c-1f3fc' => '1f93c',
            '1f93c-1f3fd' => '1f93c',
            '1f93c-1f3fe' => '1f93c',
            '1f93c-1f3ff' => '1f93c',
        ];

        $fromCodePoint = function ($code) {
            $num = intval($code, 16);

            if ($num <= 0x7F) {
                return chr($num);
            }

            if ($num <= 0x7FF) {
                return chr(($num >> 6) + 192).chr(($num & 63) + 128);
            }

            if ($num <= 0xFFFF) {
                return chr(($num >> 12) + 224).chr((($num >> 6) & 63) + 128).chr(($num & 63) + 128);
            }

            return chr(($num >> 18) + 240).chr((($num >> 12) & 63) + 128).chr((($num >> 6) & 63) + 128).chr(($num & 63) + 128);
        };

        $convertEmojioneToTwemoji = function ($code) use ($emojioneToTwemojiMap) {
            if (isset($emojioneToTwemojiMap[$code])) {
                return $emojioneToTwemojiMap[$code];
            }

            return  ltrim($code, '0');
        };

        $posts = $schema->getConnection()->table('posts')
            ->select('id', 'content')
            ->where('content', 'like', '%<EMOJI%')
            ->cursor();

        foreach ($posts as $post) {
            $content = preg_replace_callback(
                '/<EMOJI seq="(.+?)">.+?<\/EMOJI>/',
                function ($m) use ($convertEmojioneToTwemoji, $fromCodePoint) {
                    $code = $convertEmojioneToTwemoji($m[1]);
                    $codepoints = explode('-', $code);

                    return implode('', array_map($fromCodePoint, $codepoints));
                },
                $post->content
            );

            $schema->getConnection()->table('posts')
                ->where('id', $post->id)
                ->update(['content' => $content]);
        }
    },

    'down' => function (Builder $schema) {
        // not implemented
    }
];
