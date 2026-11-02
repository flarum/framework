<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Ideation\GitHub;

use Flarum\Frontend\Document;
use Flarum\Settings\Event\Saving;
use Flarum\Settings\SettingsRepositoryInterface;

/**
 * Keeps the GitHub token and webhook secret on the server.
 *
 * The admin page gets every setting, so these two are blanked there and the
 * page is told only whether each is saved. Saving the form with the field
 * left empty keeps what is stored; the page's "Remove" sends null instead.
 */
class Secrets
{
    public const KEYS = ['token', 'secret'];

    public function __construct(
        protected SettingsRepositoryInterface $settings
    ) {
    }

    /** Admin frontend content, after core's payload has added the settings. */
    public function __invoke(Document $document): void
    {
        $saved = [];

        foreach (self::KEYS as $name) {
            $key = Config::PREFIX.$name;
            $saved[$name] = (string) $this->settings->get($key) !== '';

            if (isset($document->payload['settings'][$key])) {
                $document->payload['settings'][$key] = '';
            }
        }

        $document->payload['ideationGithubSecrets'] = $saved;
    }

    public function handle(Saving $event): void
    {
        foreach (self::KEYS as $name) {
            $key = Config::PREFIX.$name;

            if (! array_key_exists($key, $event->settings)) {
                continue;
            }

            if ($event->settings[$key] === null) {
                $event->settings[$key] = '';
            } elseif (trim((string) $event->settings[$key]) === '') {
                unset($event->settings[$key]);
            }
        }
    }
}
