<?php
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Api\Serializers;

class PostBasicSerializer extends Serializer
{
    /**
     * {@inheritdoc}
     */
    protected $type = 'posts';

    /**
     * {@inheritdoc}
     */
    protected function getDefaultAttributes($post)
    {
        $attributes = [
            'id'          => (int) $post->id,
            'number'      => (int) $post->number,
            'time'        => $post->time->toRFC3339String(),
            'contentType' => $post->type
        ];

        if ($post->type === 'comment') {
            $attributes['contentHtml'] = $post->content_html;
        } else {
            $attributes['content'] = $post->content;
        }

        return $attributes;
    }

    /**
     * @return callable
     */
    public function user()
    {
        return $this->hasOne('Flarum\Api\Serializers\UserBasicSerializer');
    }

    /**
     * @return callable
     */
    public function discussion()
    {
        return $this->hasOne('Flarum\Api\Serializers\DiscussionBasicSerializer');
    }
}
