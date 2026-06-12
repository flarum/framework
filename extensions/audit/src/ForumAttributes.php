<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Audit;

use Flarum\Api\Context;
use Flarum\Api\Schema;
use Flarum\Audit\Search\AuditGambits;
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\User\User;
use Illuminate\Support\Str;

class ForumAttributes
{
    /**
     * @return array<Schema\Attribute>
     */
    public function __invoke(): array
    {
        return [
            Schema\Boolean::make('canAudit')
                ->get(fn ($forum, Context $context) => $this->access($context->getActor())['canAudit']),
            Schema\Boolean::make('canAuditUser')
                ->get(fn ($forum, Context $context) => $this->access($context->getActor())['canAuditUser']),
            Schema\Boolean::make('canAuditDiscussion')
                ->get(fn ($forum, Context $context) => $this->access($context->getActor())['canAuditDiscussion']),
            Schema\Arr::make('auditFilters')
                ->get(fn ($forum, Context $context) => $this->access($context->getActor())['auditFilters']),
            Schema\Arr::make('auditActions')
                ->get(fn ($forum, Context $context) => $this->access($context->getActor())['auditActions']),
        ];
    }

    /**
     * The audit access summary for an actor, mirroring the permission/limited-access
     * logic that previously lived in the ForumSerializer attributes callback.
     *
     * Unauthorized actors get a fully-disabled, empty set (the 1.x serializer returned
     * no keys at all; the field-based resource always serialises every field, so the
     * disabled defaults below are the equivalent the frontend's truthiness checks see).
     *
     * @return array{canAudit: bool, canAuditUser: bool, canAuditDiscussion: bool, auditFilters: array, auditActions: array}
     */
    protected function access(User $actor): array
    {
        $blank = [
            'canAudit' => false,
            'canAuditUser' => false,
            'canAuditDiscussion' => false,
            'auditFilters' => [],
            'auditActions' => [],
        ];

        if ($actor->hasPermission('flarum-audit.view')) {
            return [
                'canAudit' => true,
                'canAuditUser' => true,
                'canAuditDiscussion' => true,
                'auditFilters' => $this->filters($actor),
                'auditActions' => $this->actions(null),
            ];
        }

        if ($actor->hasPermission('flarum-audit.viewLimited')) {
            /** @var SettingsRepositoryInterface $settings */
            $settings = resolve(SettingsRepositoryInterface::class);

            $limitedActions = $settings->get('flarum-audit.limitedActions');

            // If the setting has no value, it means everything is allowed
            if (! $limitedActions) {
                return [
                    'canAudit' => true,
                    'canAuditUser' => true,
                    'canAuditDiscussion' => true,
                    'auditFilters' => $this->filters($actor),
                    'auditActions' => $this->actions(null),
                ];
            }

            $canAuditUser = false;
            $canAuditDiscussion = false;

            foreach (explode(',', $limitedActions) as $action) {
                if (Str::startsWith($action, 'user.')) {
                    $canAuditUser = true;
                }

                if (Str::startsWith($action, 'discussion.') || Str::startsWith($action, 'post.')) {
                    $canAuditDiscussion = true;
                }
            }

            return [
                'canAudit' => true,
                'canAuditUser' => $canAuditUser,
                'canAuditDiscussion' => $canAuditDiscussion,
                'auditFilters' => $this->filters($actor),
                'auditActions' => $this->actions($limitedActions),
            ];
        }

        return $blank;
    }

    /**
     * The search filters to advertise to this actor in the audit browser.
     * The `ip` filter is hidden from actors who can't actually search by IP,
     * mirroring the IpFilter's own permission check, so we never show an unusable hint.
     *
     * @return array<array{key: string, example: string, extension: string|null}>
     */
    protected function filters(User $actor): array
    {
        $canSearchIp = $actor->hasPermission('flarum-audit.view');

        if (! $canSearchIp) {
            /** @var SettingsRepositoryInterface $settings */
            $settings = resolve(SettingsRepositoryInterface::class);
            $canSearchIp = (bool) $settings->get('flarum-audit.limitedIpAddress');
        }

        return array_values(array_filter(AuditGambits::$filters, function (array $filter) use ($canSearchIp) {
            return $filter['key'] !== 'ip' || $canSearchIp;
        }));
    }

    /**
     * The known action strings, grouped by the extension that registered them, for the
     * audit browser's `action:` autocomplete. When $limitedActions is given (a limited-access
     * actor), only actions the actor is actually allowed to view are returned, mirroring the
     * View scope so we never suggest an action that would be filtered out.
     *
     * @param string|null $limitedActions Comma-separated whitelist of allowed actions/patterns, or null for no limit.
     * @return array<string, string[]>
     */
    protected function actions(?string $limitedActions): array
    {
        $allowed = $limitedActions ? array_filter(explode(',', $limitedActions)) : null;

        $result = [];

        foreach (AuditLogger::$registeredActions as $extension => $actions) {
            $visible = $allowed === null
                ? $actions
                : array_values(array_filter($actions, function (string $action) use ($allowed) {
                    return $this->actionAllowed($action, $allowed);
                }));

            if ($visible) {
                $result[$extension] = $visible;
            }
        }

        return $result;
    }

    /**
     * @param string[] $allowed
     */
    protected function actionAllowed(string $action, array $allowed): bool
    {
        foreach ($allowed as $pattern) {
            if ($pattern === $action) {
                return true;
            }

            // Wildcard patterns like "user.*" allow any action sharing that prefix.
            if (Str::endsWith($pattern, '.*') && Str::startsWith($action, substr($pattern, 0, -1))) {
                return true;
            }
        }

        return false;
    }
}
