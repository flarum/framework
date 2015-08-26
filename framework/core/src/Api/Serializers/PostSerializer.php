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

class PostSerializer extends PostBasicSerializer
{
    /**
     * {@inheritdoc}
     */
    protected function getDefaultAttributes($post)
    {
        $attributes = parent::getDefaultAttributes($post);

        unset($attributes['content']);

        $canEdit = $post->can($this->actor, 'edit');

        if ($post->type === 'comment') {
            $attributes['contentHtml'] = $post->content_html;

            if ($canEdit) {
                $attributes['content'] = $post->content;
            }
        } else {
            $attributes['content'] = $post->content;
        }

        if ($post->edit_time) {
            $attributes['editTime'] = $post->edit_time->toRFC3339String();
        }

        if ($post->hide_time) {
            $attributes['isHidden'] = true;
            $attributes['hideTime'] = $post->hide_time->toRFC3339String();
        }

        $attributes += [
            'canEdit'   => $canEdit,
            'canDelete' => $post->can($this->actor, 'delete')
        ];

        return $attributes;
    }

    /**
     * @return callable
     */
    public function user()
    {
        return $this->hasOne('Flarum\Api\Serializers\UserSerializer');
    }

    /**
     * @return callable
     */
    public function discussion()
    {
        return $this->hasOne('Flarum\Api\Serializers\DiscussionSerializer');
    }

    /**
     * @return callable
     */
    public function editUser()
    {
        return $this->hasOne('Flarum\Api\Serializers\UserSerializer');
    }

    /**
     * @return callable
     */
    public function hideUser()
    {
        return $this->hasOne('Flarum\Api\Serializers\UserSerializer');
    }
}
