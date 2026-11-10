<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Akismet\Listener;

use Flarum\Akismet\Akismet;
use Flarum\Foundation\ValidationException;
use Flarum\Locale\TranslatorInterface;
use Flarum\Settings\Event\Saving;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Arr;
use Psr\Log\LoggerInterface;

/**
 * Checks a submitted API key against Akismet's verify-key endpoint before it
 * is saved. Without this, a typo'd key was accepted silently and the forum
 * ran unprotected with nothing to show for it.
 */
class ValidateApiKey
{
    public function __construct(
        protected Akismet $akismet,
        protected TranslatorInterface $translator,
        protected LoggerInterface $log
    ) {
    }

    public function handle(Saving $event): void
    {
        $key = Arr::get($event->settings, 'flarum-akismet.api_key');

        // Not part of this save, or deliberately being cleared.
        if ($key === null || $key === '') {
            return;
        }

        try {
            $valid = $this->akismet->verifyKey($key);
        } catch (GuzzleException $e) {
            // Can't reach Akismet right now — don't block the admin from
            // saving; a wrong key will still surface in the log on use.
            $this->log->warning("[flarum/akismet] Could not verify the API key, saving unverified: {$e->getMessage()}");

            return;
        }

        if (! $valid) {
            throw new ValidationException([
                'flarum-akismet.api_key' => $this->translator->trans('flarum-akismet.admin.akismet_settings.invalid_api_key_message'),
            ]);
        }
    }
}
