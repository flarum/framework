<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\OAuthProvider\ClientRegistration;

use Flarum\OAuthProvider\Models\Client;
use Flarum\Settings\SettingsRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;

/**
 * Which kinds of client the admin allows: self-registered ones (dynamic
 * registration and metadata documents, such as MCP clients) and the ones an
 * admin added. A client of a kind that is switched off can't authorize,
 * refresh or use its tokens; switched back on, it can again.
 */
class AllowedClients
{
    public const string SELF_REGISTERED_SETTING = 'flarum-oauth-provider.allow_self_registered_clients';
    public const string ADMIN_SETTING = 'flarum-oauth-provider.allow_admin_clients';

    public function __construct(
        protected SettingsRepositoryInterface $settings,
    ) {
    }

    public function selfRegistered(): bool
    {
        return filter_var($this->settings->get(self::SELF_REGISTERED_SETTING, true), FILTER_VALIDATE_BOOLEAN);
    }

    public function admin(): bool
    {
        return filter_var($this->settings->get(self::ADMIN_SETTING, true), FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * @return list<string>
     */
    public function sources(): array
    {
        return [
            ...($this->admin() ? [Client::SOURCE_ADMIN] : []),
            ...($this->selfRegistered() ? [Client::SOURCE_METADATA_DOCUMENT, Client::SOURCE_DYNAMIC] : []),
        ];
    }

    public function allows(Client $client): bool
    {
        return in_array($client->source, $this->sources(), true);
    }

    /**
     * Limits a query of clients to the kinds allowed.
     */
    public function scope(Builder $query): Builder
    {
        return $query->whereIn($query->getModel()->qualifyColumn('source'), $this->sources());
    }
}
