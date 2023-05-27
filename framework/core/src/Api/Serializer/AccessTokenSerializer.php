<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Api\Serializer;

use Flarum\Http\AccessToken;
use Flarum\Locale\TranslatorInterface;
use InvalidArgumentException;
use Jenssegers\Agent\Agent;

class AccessTokenSerializer extends AbstractSerializer
{
    protected $type = 'access-tokens';

    public function __construct(
        protected TranslatorInterface $translator
    ) {
    }

    protected function getDefaultAttributes(object|array $model): array
    {
        if (! ($model instanceof AccessToken)) {
            throw new InvalidArgumentException(
                get_class($this).' can only serialize instances of '.AccessToken::class
            );
        }

        $session = $this->request->getAttribute('session');

        $agent = new Agent();
        $agent->setUserAgent($model->last_user_agent);

        $attributes = [
            'token' => $model->token,
            'userId' => $model->user_id,
            'createdAt' => $this->formatDate($model->created_at),
            'lastActivityAt' => $this->formatDate($model->last_activity_at),
            'isCurrent' => $session && $session->get('access_token') === $model->token,
            'isSessionToken' => in_array($model->type, ['session', 'session_remember'], true),
            'title' => $model->title,
            'lastIpAddress' => $model->last_ip_address,
            'device' => $this->translator->trans('core.forum.security.browser_on_operating_system', [
                'browser' => $agent->browser(),
                'os' => $agent->platform(),
            ]),
        ];

        // Unset hidden attributes (like the token value on session tokens)
        foreach ($model->getHidden() as $name) {
            unset($attributes[$name]);
        }

        // Hide the token value to non-actors no matter who they are.
        if (isset($attributes['token']) && $this->getActor()->id !== $model->user_id) {
            unset($attributes['token']);
        }

        return $attributes;
    }
}
