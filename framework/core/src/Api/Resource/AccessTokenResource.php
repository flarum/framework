<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Api\Resource;

use Flarum\Api\Context;
use Flarum\Api\Endpoint;
use Flarum\Api\Schema;
use Flarum\Http\AccessToken;
use Flarum\Http\DeveloperAccessToken;
use Flarum\Http\Event\DeveloperTokenCreated;
use Flarum\Http\RememberAccessToken;
use Flarum\Http\SessionAccessToken;
use Flarum\Locale\TranslatorInterface;
use Flarum\User\Exception\PermissionDeniedException;
use Illuminate\Contracts\Session\Session;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Jenssegers\Agent\Agent;

/**
 * @extends AbstractDatabaseResource<AccessToken>
 */
class AccessTokenResource extends AbstractDatabaseResource
{
    public function __construct(
        protected TranslatorInterface $translator
    ) {
    }

    public function type(): string
    {
        return 'access-tokens';
    }

    public function model(): string
    {
        return AccessToken::class;
    }

    public function scope(Builder $query, \Tobyz\JsonApiServer\Context $context): void
    {
        $query->whereVisibleTo($context->getActor());
    }

    public function newModel(\Tobyz\JsonApiServer\Context $context): object
    {
        if ($context->creating(self::class)) {
            $token = DeveloperAccessToken::make($context->getActor()->id);
            $token->last_activity_at = null;

            return $token;
        }

        return parent::newModel($context);
    }

    public function endpoints(): array
    {
        return [
            Endpoint\Create::make()
                ->authenticated()
                ->can('createAccessToken'),
            Endpoint\Delete::make()
                ->authenticated(),
            Endpoint\Index::make()
                ->authenticated()
                ->paginate(),
        ];
    }

    public function fields(): array
    {
        return [
            Schema\Str::make('token')
                ->visible(function (AccessToken $token, Context $context) {
                    return $context->getActor()->id === $token->user_id && ! in_array('token', $token->getHidden(), true);
                }),
            Schema\Integer::make('userId'),
            Schema\DateTime::make('createdAt'),
            Schema\DateTime::make('lastActivityAt'),
            Schema\Boolean::make('isCurrent')
                ->get(function (AccessToken $token, Context $context) {
                    return $token->token === $context->request->getAttribute('session')->get('access_token');
                }),
            Schema\Boolean::make('isSessionToken')
                ->get(function (AccessToken $token) {
                    return in_array($token->type, [SessionAccessToken::$type, RememberAccessToken::$type], true);
                }),
            Schema\Str::make('title')
                ->writableOnCreate()
                ->requiredOnCreate()
                ->maxLength(255),
            Schema\Str::make('lastIpAddress'),
            Schema\Str::make('device')
                ->get(function (AccessToken $token) {
                    $agent = new Agent();
                    $agent->setUserAgent($token->last_user_agent);

                    return $this->translator->trans('core.forum.security.browser_on_operating_system', [
                        'browser' => $agent->browser(),
                        'os' => $agent->platform(),
                    ]);
                }),
        ];
    }

    public function created(object $model, \Tobyz\JsonApiServer\Context $context): ?object
    {
        $this->events->dispatch(new DeveloperTokenCreated($model));

        return parent::created($model, $context);
    }

    /**
     * @param AccessToken $model
     * @param \Flarum\Api\Context $context
     * @throws PermissionDeniedException
     */
    public function delete(object $model, \Tobyz\JsonApiServer\Context $context): void
    {
        /** @var Session|null $session */
        $session = $context->request->getAttribute('session');

        // Current session should only be terminated through logout.
        if ($session && $model->token === $session->get('access_token')) {
            throw new PermissionDeniedException();
        }

        // Don't give away the existence of the token.
        if ($context->getActor()->cannot('revoke', $model)) {
            throw new ModelNotFoundException();
        }

        $model->delete();
    }
}
