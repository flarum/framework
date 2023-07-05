<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Api\Serializer;

use Exception;
use Flarum\Foundation\ErrorHandling\LogReporter;
use Flarum\Locale\TranslatorInterface;
use Flarum\Post\CommentPost;
use Flarum\Post\Post;
use InvalidArgumentException;
use Tobscure\JsonApi\Relationship;

class BasicPostSerializer extends AbstractSerializer
{
    protected $type = 'posts';

    public function __construct(
        protected LogReporter $log,
        protected TranslatorInterface $translator
    ) {
    }

    /**
     * @throws InvalidArgumentException
     */
    protected function getDefaultAttributes(object|array $model): array
    {
        if (! ($model instanceof Post)) {
            throw new InvalidArgumentException(
                get_class($this).' can only serialize instances of '.Post::class
            );
        }

        $attributes = [
            'number'      => (int) $model->number,
            'createdAt'   => $this->formatDate($model->created_at),
            'contentType' => $model->type
        ];

        if ($model instanceof CommentPost) {
            try {
                $attributes['contentHtml'] = $model->formatContent($this->request);
                $attributes['renderFailed'] = false;
            } catch (Exception $e) {
                $attributes['contentHtml'] = $this->translator->trans('core.lib.error.render_failed_message');
                $this->log->report($e);
                $attributes['renderFailed'] = true;
            }
        } else {
            $attributes['content'] = $model->content;
        }

        return $attributes;
    }

    protected function user(Post $post): ?Relationship
    {
        return $this->hasOne($post, BasicUserSerializer::class);
    }

    protected function discussion(Post $post): ?Relationship
    {
        return $this->hasOne($post, BasicDiscussionSerializer::class);
    }
}
