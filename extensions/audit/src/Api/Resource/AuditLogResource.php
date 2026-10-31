<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Audit\Api\Resource;

use Flarum\Api\Context as FlarumContext;
use Flarum\Api\Endpoint;
use Flarum\Api\Resource\AbstractDatabaseResource;
use Flarum\Api\Schema;
use Flarum\Api\Sort\SortColumn;
use Flarum\Audit\AuditLog;
use Flarum\Settings\SettingsRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Tobyz\JsonApiServer\Context;

/**
 * @extends AbstractDatabaseResource<AuditLog>
 */
class AuditLogResource extends AbstractDatabaseResource
{
    public function type(): string
    {
        return 'audit';
    }

    public function model(): string
    {
        return AuditLog::class;
    }

    public function scope(Builder $query, Context $context): void
    {
        // Visibility is enforced by AuditSearcher::getQuery(), which applies
        // whereVisibleTo($actor) (running Scope\View via the ModelVisibility
        // extender). Audit is search-only — there are no Show/Create/etc.
        // endpoints — so no additional scoping is needed here.
    }

    public function endpoints(): array
    {
        return [
            Endpoint\Index::make()
                ->paginate(24, 50)
                ->defaultSort('-createdAt')
                ->addDefaultInclude([
                    'actor',
                    'discussion',
                    'newDiscussion',
                    'post',
                    'post.discussion',
                    'post.user',
                    'user',
                ]),
        ];
    }

    public function sorts(): array
    {
        return [
            SortColumn::make('createdAt'),
        ];
    }

    public function fields(): array
    {
        return [
            Schema\Integer::make('actorId')
                ->property('actor_id')
                ->nullable(),
            Schema\Str::make('client'),
            Schema\Str::make('ipAddress')
                ->nullable()
                ->get(fn (AuditLog $log, FlarumContext $context) => $this->ipAddress($log, $context)),
            Schema\Str::make('action'),
            Schema\Arr::make('payload'),
            Schema\DateTime::make('createdAt')
                ->property('created_at'),

            // `actor` is a real BelongsTo relation. The remaining relations are
            // accessors on AuditLog (getXAttribute), so each gets an explicit
            // ->get() to resolve through the accessor rather than the adapter.
            Schema\Relationship\ToOne::make('actor')
                ->type('users')
                ->includable(),
            Schema\Relationship\ToOne::make('discussion')
                ->type('discussions')
                ->includable()
                ->get(fn (AuditLog $log) => $log->discussion),
            Schema\Relationship\ToOne::make('newDiscussion')
                ->type('discussions')
                ->includable()
                ->get(fn (AuditLog $log) => $log->newDiscussion),
            Schema\Relationship\ToOne::make('post')
                ->type('posts')
                ->includable()
                ->get(fn (AuditLog $log) => $log->post),
            Schema\Relationship\ToOne::make('user')
                ->type('users')
                ->includable()
                ->get(fn (AuditLog $log) => $log->user),
            // The `tag` relationship is added conditionally in extend.php when
            // the flarum-tags extension is enabled.
        ];
    }

    protected function ipAddress(AuditLog $log, FlarumContext $context): ?string
    {
        $actor = $context->getActor();

        if (! $actor->hasPermission('flarum-audit.view')) {
            /** @var SettingsRepositoryInterface $settings */
            $settings = resolve(SettingsRepositoryInterface::class);

            if (! $settings->get('flarum-audit.limitedIpAddress')) {
                return null;
            }
        }

        return $log->ip_address;
    }
}
