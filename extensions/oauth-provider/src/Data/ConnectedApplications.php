<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\OAuthProvider\Data;

use Flarum\Gdpr\Data\Type;
use Flarum\OAuthProvider\Models\AccessToken;
use Flarum\OAuthProvider\Models\AuthCode;
use Flarum\OAuthProvider\Models\Consent;
use Flarum\OAuthProvider\Models\RefreshToken;

/**
 * The applications a member connected, for flarum/gdpr: listed in the export,
 * and all disconnected when the member is anonymised or erased: their
 * consents, codes, access tokens and refresh tokens all go.
 */
class ConnectedApplications extends Type
{
    public function export(): ?array
    {
        $applications = Consent::query()
            ->with('client')
            ->where('user_id', $this->user->id)
            ->orderBy('created_at')
            ->get()
            ->map(fn (Consent $consent) => [
                'application' => $consent->client?->name,
                'client_id' => $consent->client_id,
                'scopes' => array_values($consent->scopes ?? []),
                'connected_at' => $consent->created_at?->toAtomString(),
            ])
            ->all();

        return $applications ? [['oauth/connected-applications.json' => $this->encodeForExport($applications)]] : null;
    }

    public function anonymize(): void
    {
        $this->delete();
    }

    public function delete(): void
    {
        $tokenIds = AccessToken::query()->select('id')->where('user_id', $this->user->id);

        // Explicitly, not by the foreign key's cascade: SQLite doesn't enforce it.
        RefreshToken::query()->whereIn('access_token_id', $tokenIds)->delete();
        AccessToken::query()->where('user_id', $this->user->id)->delete();
        AuthCode::query()->where('user_id', $this->user->id)->delete();
        Consent::query()->where('user_id', $this->user->id)->delete();
    }
}
