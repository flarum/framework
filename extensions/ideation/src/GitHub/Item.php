<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Ideation\GitHub;

/**
 * GitHub's issue, pull request and milestone objects, reduced to what an idea
 * shows. The REST API and the webhooks send the same objects, so both paths
 * go through here.
 *
 * Issue states are open, completed or not_planned; pull requests open,
 * merged or closed (unmerged); milestones open or closed.
 */
final class Item
{
    /** @return array{type: string, title: string, state: string, open_issues: null, closed_issues: null, updated_at: ?string} */
    public static function issue(array $data): array
    {
        $isPull = isset($data['pull_request']) || array_key_exists('merged', $data);

        if ($isPull) {
            $merged = ! empty($data['merged']) || ! empty($data['merged_at']) || ! empty($data['pull_request']['merged_at']);
            $state = ($data['state'] ?? 'open') === 'open' ? 'open' : ($merged ? 'merged' : 'closed');
        } else {
            $state = ($data['state'] ?? 'open') === 'open'
                ? 'open'
                : (($data['state_reason'] ?? '') === 'not_planned' ? 'not_planned' : 'completed');
        }

        return [
            'type' => $isPull ? 'pull' : 'issue',
            'title' => mb_substr((string) ($data['title'] ?? ''), 0, 255),
            'state' => $state,
            'open_issues' => null,
            'closed_issues' => null,
            'updated_at' => isset($data['updated_at']) ? (string) $data['updated_at'] : null,
        ];
    }

    /** @return array{type: string, title: string, state: string, open_issues: int, closed_issues: int, updated_at: ?string} */
    public static function milestone(array $data): array
    {
        return [
            'type' => 'milestone',
            'title' => mb_substr((string) ($data['title'] ?? ''), 0, 255),
            'state' => ($data['state'] ?? 'open') === 'open' ? 'open' : 'closed',
            'open_issues' => max(0, (int) ($data['open_issues'] ?? 0)),
            'closed_issues' => max(0, (int) ($data['closed_issues'] ?? 0)),
            'updated_at' => isset($data['updated_at']) ? (string) $data['updated_at'] : null,
        ];
    }

    /** Whether the work this item stands for is finished. Null: it no longer counts either way. */
    public static function done(string $type, string $state): ?bool
    {
        return match ($type) {
            'issue' => $state === 'open' ? false : ($state === 'completed' ? true : null),
            'pull' => $state === 'open' ? false : ($state === 'merged' ? true : null),
            default => $state === 'closed',
        };
    }
}
