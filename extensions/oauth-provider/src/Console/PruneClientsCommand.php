<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\OAuthProvider\Console;

use Carbon\Carbon;
use Flarum\OAuthProvider\Models\AccessToken;
use Flarum\OAuthProvider\Models\AuthCode;
use Flarum\OAuthProvider\Models\Client;
use Flarum\OAuthProvider\Models\Consent;
use Illuminate\Console\Command;

/**
 * Deletes self-described clients (metadata document, dynamic registration)
 * that nobody ever authorized: anyone can create them without signing in.
 */
class PruneClientsCommand extends Command
{
    public const int MAX_AGE_DAYS = 30;

    protected $signature = 'oauth:prune-clients';

    protected $description = 'Deletes unused self-registered OAuth clients older than '.self::MAX_AGE_DAYS.' days.';

    public function handle(): void
    {
        $deleted = Client::query()
            ->where('source', '!=', Client::SOURCE_ADMIN)
            ->where('created_at', '<', Carbon::now()->subDays(self::MAX_AGE_DAYS))
            ->whereNotIn('id', Consent::query()->select('client_id'))
            ->whereNotIn('id', AccessToken::query()->select('client_id'))
            ->whereNotIn('id', AuthCode::query()->select('client_id'))
            ->delete();

        $this->info("Deleted $deleted unused self-registered client(s).");
    }
}
