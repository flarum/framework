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
use Flarum\Mail\Job\SendAbandonedExtensionsEmailJob;
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\User\User;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Contracts\Queue\Queue;
use RuntimeException;
use Symfony\Contracts\Translation\TranslatorInterface;

class AbandonedExtensionsFetcher
{
    public const SETTINGS_KEY = 'flarum-core.abandoned_extensions_map';
    public const NOTIFY_ADMINS_SETTING = 'flarum-core.notify_admins_on_abandoned';

    protected const SOURCE_URL = 'https://raw.githubusercontent.com/flarum/abandoned-extensions/main/abandoned.json';

    public function __construct(
        protected ExtensionManager $extensions,
        protected SettingsRepositoryInterface $settings,
        protected Client $client,
        protected Queue $queue,
        protected TranslatorInterface $translator,
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
        $map = $this->fetch();
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
            // Manual trigger: notify about all installed abandoned extensions.
            // Scheduled trigger: only notify about newly detected ones.
            $toNotify = $manual ? array_keys($filtered) : $new;

            if ($toNotify) {
                $this->notifyAdmins($toNotify, $filtered);
            }
        }

        return ['count' => count($filtered), 'new' => $new];
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

        $lines = array_map(function (string $package) use ($map) {
            $replacement = $map[$package]['replacement'] ?? null;

            return $replacement
                ? $this->translator->trans('core.email.abandoned_extensions.line_with_replacement', compact('package', 'replacement'))
                : $this->translator->trans('core.email.abandoned_extensions.line_no_replacement', compact('package'));
        }, $newPackages);

        $subject = $this->translator->trans('core.email.abandoned_extensions.subject');
        $forumTitle = $this->settings->get('forum_title', '');

        foreach ($admins as $admin) {
            $this->queue->push(new SendAbandonedExtensionsEmailJob(
                email: $admin->email,
                username: $admin->display_name,
                subject: $subject,
                extensionLines: $lines,
                forumTitle: $forumTitle,
            ));
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
