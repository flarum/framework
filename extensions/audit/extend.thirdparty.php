<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Audit;

use ClarkWinkelmann\AuthorChange\Event as AuthorChangeEvent;
use Flarum\Audit\Extend\Audit;
use Flarum\Extend\Conditional;
use FoF\BanIPs\Events as BanIPsEvents;
use FoF\Impersonate\Events\Impersonated;
use FoF\MergeDiscussions\Events\DiscussionWasMerged;
use FoF\Split\Events\DiscussionWasSplit;
use FoF\UserBio\Event\BioChanged;

/*
 * Audit integrations for third-party extensions.
 *
 * These are kept in flarum/audit for now so the migrated extension preserves its existing
 * coverage. Because they reference classes from extensions that aren't part of the Flarum
 * monorepo, this file is intentionally excluded from the monorepo's PHPStan analysis.
 *
 * Going forward, each third-party extension should declare its own integration in its own
 * extend.php using the public Flarum\Audit\Extend\Audit extender, gated behind
 * (new Flarum\Extend\Conditional())->whenExtensionEnabled('flarum-audit', ...). Once an
 * extension adopts the public API, its block here can be removed.
 */
return [
    (new Conditional())
        ->whenExtensionEnabled('clarkwinkelmann-author-change', function () {
            return [
                (new Audit())
                    ->group('clarkwinkelmann-author-change')
                    ->listen(AuthorChangeEvent\DiscussionCreateDateChanged::class, 'discussion.create_date_changed', function ($e) {
                        return [
                            'discussion_id' => $e->discussion->id,
                            'old_date' => $e->oldDate->toIso8601String(),
                            'new_date' => $e->discussion->created_at->toIso8601String(),
                        ];
                    })
                    ->listen(AuthorChangeEvent\DiscussionUserChanged::class, 'discussion.user_changed', function ($e) {
                        return [
                            'discussion_id' => $e->discussion->id,
                            'old_user_id' => optional($e->oldUser)->id,
                            'new_user_id' => optional($e->discussion->user)->id,
                        ];
                    })
                    ->listen(AuthorChangeEvent\PostCreateDateChanged::class, 'post.create_date_changed', function ($e) {
                        return [
                            'post_id' => $e->post->id,
                            'discussion_id' => $e->post->discussion->id,
                            'old_date' => $e->oldDate->toIso8601String(),
                            'new_date' => $e->post->created_at->toIso8601String(),
                        ];
                    })
                    ->listen(AuthorChangeEvent\PostEditDateChanged::class, 'post.edit_date_changed', function ($e) {
                        return [
                            'post_id' => $e->post->id,
                            'discussion_id' => $e->post->discussion->id,
                            'old_date' => optional($e->oldDate)->toIso8601String(),
                            'new_date' => optional($e->post->edited_at)->toIso8601String(),
                        ];
                    })
                    ->listen(AuthorChangeEvent\PostUserChanged::class, 'post.user_changed', function ($e) {
                        return [
                            'post_id' => $e->post->id,
                            'discussion_id' => $e->post->discussion->id,
                            'old_user_id' => optional($e->oldUser)->id,
                            'new_user_id' => optional($e->post->user)->id,
                        ];
                    }),
            ];
        })
        ->whenExtensionEnabled('fof-ban-ips', function () {
            return [
                (new Audit())
                    ->group('fof-ban-ips')
                    ->listen(BanIPsEvents\IPWasBanned::class, 'fof_ban_ips.banned', function ($e) {
                        return array_filter([
                            'ip' => $e->bannedIP->address,
                            'reason' => $e->bannedIP->reason,
                            'user_id' => $e->bannedIP->user_id ?: null,
                        ], function ($v) {
                            return $v !== null;
                        });
                    })
                    ->listen(BanIPsEvents\IPWasUnbanned::class, 'fof_ban_ips.unbanned', function ($e) {
                        return array_filter([
                            'ip' => $e->unbannedIP->address,
                            'user_id' => $e->unbannedIP->user_id ?: null,
                        ], function ($v) {
                            return $v !== null;
                        });
                    }),
            ];
        })
        ->whenExtensionEnabled('fof-impersonate', function () {
            return [
                (new Audit())
                    ->group('fof-impersonate')
                    ->listen(Impersonated::class, 'user.impersonated', function ($e) {
                        return [
                            'user_id' => $e->user->id,
                            'reason' => $e->switchReason ?: null,
                        ];
                    }),
            ];
        })
        ->whenExtensionEnabled('fof-merge-discussions', function () {
            return [
                (new Audit())
                    ->group('fof-merge-discussions')
                    ->register('discussion.merged_away', 'discussion.merged_into')
                    ->using(function () {
                        // Merge dispatches multiple logs per event, so it uses a raw listener.
                        resolve('events')->listen(DiscussionWasMerged::class, function (DiscussionWasMerged $event) {
                            foreach ($event->mergedDiscussions as $discussion) {
                                AuditLogger::log('discussion.merged_away', [
                                    'discussion_id' => $discussion->id,
                                    'new_discussion_id' => $event->discussion->id,
                                ]);
                            }

                            AuditLogger::log('discussion.merged_into', [
                                'discussion_id' => $event->discussion->id,
                                'original_discussion_ids' => $event->mergedDiscussions->pluck('id')->all(),
                                'post_count' => $event->posts->count(),
                            ]);
                        });
                    }),
            ];
        })
        ->whenExtensionEnabled('fof-split', function () {
            return [
                (new Audit())
                    ->group('fof-split')
                    ->register('discussion.split_away', 'discussion.split_into')
                    ->using(function () {
                        resolve('events')->listen(DiscussionWasSplit::class, function (DiscussionWasSplit $event) {
                            AuditLogger::log('discussion.split_away', [
                                'discussion_id' => $event->originalDiscussion->id,
                                'new_discussion_id' => $event->newDiscussion->id,
                                'post_count' => $event->posts->count(),
                            ]);
                            AuditLogger::log('discussion.split_into', [
                                'discussion_id' => $event->newDiscussion->id,
                                'original_discussion_id' => $event->originalDiscussion->id,
                                'post_count' => $event->posts->count(),
                            ]);
                        });
                    }),
            ];
        })
        ->whenExtensionEnabled('fof-user-bio', function () {
            return [
                (new Audit())
                    ->group('fof-user-bio')
                    ->listen(BioChanged::class, 'user.bio_changed', function ($e) {
                        return ['user_id' => $e->user->id];
                    }),
            ];
        })
        ->whenExtensionEnabled('fof-username-request', function () {
            return [
                (new Audit())
                    ->group('fof-username-request')
                    ->using(new Integration\FoFUsernameRequestIntegration()),
            ];
        }),
];
