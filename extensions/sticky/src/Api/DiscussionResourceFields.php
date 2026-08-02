<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Sticky\Api;

use Flarum\Api\Context;
use Flarum\Api\Resource\EloquentBuffer;
use Flarum\Api\Schema;
use Flarum\Discussion\Discussion;
use Flarum\Post\CommentPost;
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\Sticky\Event\DiscussionWasStickied;
use Flarum\Sticky\Event\DiscussionWasUnstickied;
use s9e\TextFormatter\Utils;

class DiscussionResourceFields
{
    public function __invoke(): array
    {
        return [
            Schema\Boolean::make('isSticky')
                ->writable(function (Discussion $discussion, Context $context) {
                    return $context->updating()
                        && $context->getActor()->can('sticky', $discussion);
                })
                ->set(function (Discussion $discussion, bool $isSticky, Context $context) {
                    $actor = $context->getActor();

                    if ($discussion->is_sticky === $isSticky) {
                        return;
                    }

                    $discussion->is_sticky = $isSticky;

                    $discussion->raise(
                        $discussion->is_sticky
                            ? new DiscussionWasStickied($discussion, $actor)
                            : new DiscussionWasUnstickied($discussion, $actor)
                    );
                }),
            Schema\Boolean::make('canSticky')
                ->get(fn (Discussion $discussion, Context $context) => $context->getActor()->can('sticky', $discussion)),
            Schema\Str::make('firstPostExcerpt')
                ->nullable()
                ->visible(function (Discussion $discussion) {
                    return $discussion->is_sticky
                        && (bool) resolve(SettingsRepositoryInterface::class)->get('flarum-sticky.enable_display_excerpt');
                })
                ->get(function (Discussion $discussion, Context $context) {
                    // Batch the sticky rows' first posts through the
                    // relationship buffer: one query per page, non-sticky
                    // rows untouched. Pre-loading the relation at the
                    // endpoint instead would mark it loaded (null) on every
                    // row and break clients that explicitly include
                    // firstPost for all discussions, like fof/synopsis.
                    EloquentBuffer::add($discussion, 'firstPost');

                    return function () use ($discussion, $context) {
                        if (! $discussion->relationLoaded('firstPost')) {
                            $resource = $context->collection;

                            /** @var Schema\Relationship\ToOne|null $relationship */
                            $relationship = $resource instanceof \Tobyz\JsonApiServer\Resource\Resource
                                ? collect($context->fields($resource))->first(fn ($field) => $field->name === 'firstPost')
                                : null;

                            EloquentBuffer::load($discussion, 'firstPost', $relationship, $context);
                        }

                        $post = $discussion->getRelation('firstPost');

                        if (! $post instanceof CommentPost || empty($post->parsed_content)) {
                            return null;
                        }

                        // Plain text straight from the stored XML: no
                        // formatter render, no extension callbacks, no
                        // per-post policies — the things that made including
                        // the whole post expensive.
                        $plain = trim(preg_replace('/\s+/', ' ', Utils::removeFormatting($post->parsed_content)) ?? '');

                        // The frontend truncates to its own display length;
                        // cap here only to keep pathological first posts off
                        // the wire.
                        return mb_substr($plain, 0, 200);
                    };
                }),
        ];
    }
}
