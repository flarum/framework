<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Extension;

use Flarum\Foundation\Application;
use Flarum\Group\Group;
use Flarum\Locale\TranslatorInterface;
use Flarum\Mail\Job\SendAbandonedExtensionsEmailJob;
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\User\User;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Contracts\Queue\Queue;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Throwable;

class AbandonedExtensionsFetcher
{
    public const SETTINGS_KEY = 'flarum-core.abandoned_extensions_map';
    public const NOTIFY_ADMINS_SETTING = 'flarum-core.notify_admins_on_abandoned';

    /**
     * Abandoned packages that admins have not yet been notified about.
     */
    public const PENDING_NOTIFICATION_KEY = 'flarum-core.abandoned_extensions_pending_notification';

    protected const SOURCE_URL = 'https://raw.githubusercontent.com/flarum/abandoned-extensions/main/abandoned.json';

    public function __construct(
        protected ExtensionManager $extensions,
        protected SettingsRepositoryInterface $settings,
        protected Client $client,
        protected Queue $queue,
        protected TranslatorInterface $translator,
        protected ?LoggerInterface $logger = null,
    ) {
    }

    /**
     * Fetch the upstream abandoned extensions list, filter to installed packages,
     * persist the result to settings, and optionally notify admins.
     *
     * When $notify is true and the notify-admins setting is enabled:
     * - On a scheduled (automatic) run: only notifies about packages newly flagged
     *   since the last sync, to avoid repeating the same email every week.
     * - On a manual run ($manual = true): notifies about all currently installed
     *   abandoned extensions, since the admin explicitly requested the check.
     *
     * @throws RuntimeException
     * @return array{count: int, new: string[]}
     */
    public function sync(bool $notify = false, bool $manual = false): array
    {
        try {
            $map = $this->fetch();
        } catch (RuntimeException $e) {
            $this->logger?->warning($e->getMessage());

            throw $e;
        }

        $installed = $this->installedPackageNames();

        $filtered = array_filter(
            $map,
            function (string $name) use ($installed) {
                return isset($installed[$name]);
            },
            ARRAY_FILTER_USE_KEY
        );

        $previous = static::getCachedMap($this->settings);
        $new = array_keys(array_diff_key($filtered, $previous));

        $this->settings->set(self::SETTINGS_KEY, json_encode($filtered));

        if ($notify && $this->settings->get(self::NOTIFY_ADMINS_SETTING)) {
            // Newly flagged packages are kept until admins have been notified about
            // them. Otherwise a notification that failed (the mail server was down,
            // say) would never be retried: by the next sync, they are no longer new.
            $stored = $this->pendingNotification();
            $pending = array_values(array_intersect(array_unique([...$stored, ...$new]), array_keys($filtered)));

            if ($pending !== $stored) {
                $this->settings->set(self::PENDING_NOTIFICATION_KEY, json_encode($pending));
            }

            // Manual trigger: notify about all installed abandoned extensions.
            // Scheduled trigger: only notify about those not yet notified.
            $toNotify = $manual ? array_keys($filtered) : $pending;

            if ($toNotify) {
                try {
                    $this->notifyAdmins($toNotify, $filtered);
                } catch (Throwable $e) {
                    $this->logger?->error('Could not notify admins about abandoned extensions, so they will be notified on the next sync: '.$e->getMessage());

                    throw $e;
                }
            }

            if ($pending) {
                $this->settings->set(self::PENDING_NOTIFICATION_KEY, json_encode([]));
            }
        }

        return ['count' => count($filtered), 'new' => $new];
    }

    /**
     * @return string[]
     */
    protected function pendingNotification(): array
    {
        $pending = json_decode($this->settings->get(self::PENDING_NOTIFICATION_KEY) ?? '[]', true);

        return is_array($pending) ? $pending : [];
    }

    /**
     * @throws RuntimeException
     */
    protected function fetch(): array
    {
        try {
            $response = $this->client->get(self::SOURCE_URL, [
                'allow_redirects' => false,
                'timeout' => 10,
                'headers' => [
                    'Accept' => 'application/json',
                    'User-Agent' => 'Flarum/'.Application::VERSION,
                ],
            ]);
        } catch (GuzzleException $e) {
            throw new RuntimeException('Could not fetch abandoned extensions list: '.$e->getMessage(), 0, $e);
        }

        $data = json_decode((string) $response->getBody(), true);

        if (! is_array($data)) {
            throw new RuntimeException('Abandoned extensions list returned invalid JSON.');
        }

        return $data;
    }

    protected function notifyAdmins(array $newPackages, array $map): void
    {
        $admins = User::whereHas('groups', function ($q) {
            $q->where('id', Group::ADMINISTRATOR_ID);
        })->get();

        $forumTitle = $this->settings->get('forum_title', '');
        $defaultLocale = $this->settings->get('default_locale');
        $previousLocale = $this->translator->getLocale();

        try {
            foreach ($admins as $admin) {
                $locale = $admin->getPreference('locale') ?? $defaultLocale;
                $this->translator->setLocale($locale);

                $lines = array_map(function (string $package) use ($map) {
                    $replacement = $map[$package]['replacement'] ?? null;

                    return $replacement
                        ? $this->translator->trans('core.email.abandoned_extensions.line_with_replacement', compact('package', 'replacement'))
                        : $this->translator->trans('core.email.abandoned_extensions.line_no_replacement', compact('package'));
                }, $newPackages);

                $subject = $this->translator->trans('core.email.abandoned_extensions.subject');

                $this->queue->push(new SendAbandonedExtensionsEmailJob(
                    email: $admin->email,
                    username: $admin->display_name,
                    subject: $subject,
                    extensionLines: $lines,
                    forumTitle: $forumTitle,
                    locale: $locale,
                ));
            }
        } finally {
            $this->translator->setLocale($previousLocale);
        }
    }

    /**
     * Returns an associative array of composer package name => true for all
     * installed Flarum extensions.
     */
    protected function installedPackageNames(): array
    {
        $names = [];

        foreach ($this->extensions->getExtensions() as $extension) {
            $names[$extension->name] = true;
        }

        return $names;
    }

    /**
     * Return the cached map from settings, or an empty array if not yet fetched.
     *
     * @return array<string, array{replacement?: string}>
     */
    public static function getCachedMap(SettingsRepositoryInterface $settings): array
    {
        $raw = $settings->get(self::SETTINGS_KEY);

        if (! $raw) {
            return [];
        }

        $data = json_decode($raw, true);

        return is_array($data) ? $data : [];
    }
}
