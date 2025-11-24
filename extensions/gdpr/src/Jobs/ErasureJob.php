<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Gdpr\Jobs;

use Flarum\Foundation\ValidationException;
use Flarum\Gdpr\DataProcessor;
use Flarum\Gdpr\Events\Erased;
use Flarum\Gdpr\Events\Erasing;
use Flarum\Gdpr\Models\ErasureRequest;
use Flarum\Gdpr\Notifications\ErasureCompletedBlueprint;
use Flarum\Http\UrlGenerator;
use Flarum\Locale\TranslatorInterface;
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\User\User;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Filesystem\Factory;
use Illuminate\Contracts\Mail\Mailer;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Schema\Builder;
use Illuminate\Mail\Message;
use RuntimeException;

class ErasureJob extends GdprJob
{
    protected Builder $schema;
    protected TranslatorInterface $translator;
    protected Factory $filesystemFactory;
    protected SettingsRepositoryInterface $settings;
    protected UrlGenerator $url;

    public function __construct(private ErasureRequest $erasureRequest)
    {
    }

    public function handle(
        ConnectionInterface $connection,
        DataProcessor $processor,
        Dispatcher $events,
        Mailer $mailer,
        TranslatorInterface $translator,
        Factory $filesystemFactory,
        SettingsRepositoryInterface $settings,
        UrlGenerator $url,
    ): void {
        $this->translator = $translator;
        $this->filesystemFactory = $filesystemFactory;
        $this->settings = $settings;
        $this->url = $url;

        $this->schema = $connection->getSchemaBuilder();

        /** @var User */
        $user = User::find($this->erasureRequest->user_id);

        if (! $user->exists) {
            return;
        }

        // Store these props here, as they'll be erased/anonymized in a moment
        $username = $user->getDisplayNameAttribute();
        $email = $user->email;

        $mode = $this->erasureRequest->processed_mode;

        if (! $mode) {
            throw new RuntimeException('Erasure request has no mode set.');
        }

        if ($this->settings->get('flarum-gdpr.allow-anonymization') === false && $mode === ErasureRequest::MODE_ANONYMIZATION) {
            throw new ValidationException(['mode' => 'Anonymization is not enabled.']);
        }
        if ($this->settings->get('flarum-gdpr.allow-deletion') === false && $mode === ErasureRequest::MODE_DELETION) {
            throw new ValidationException(['mode' => 'Deletion is not enabled.']);
        }

        $events->dispatch(new Erasing(
            $this->erasureRequest
        ));

        $this->{$mode}($user, $processor);

        $this->sendUserConfirmation($mode, $username, $email, $mailer, $translator);

        $events->dispatch(new Erased(
            $username,
            $email,
            $mode,
            $user
        ));
    }

    private function deletion(User $user, DataProcessor $processor): void
    {
        foreach ($processor->types() as $type => $extension) {
            (new $type($user, $this->erasureRequest, $this->filesystemFactory, $this->settings, $this->url, $this->translator))->delete();
        }
    }

    private function anonymization(User $user, DataProcessor $processor): void
    {
        foreach ($processor->types() as $type => $extension) {
            (new $type($user, $this->erasureRequest, $this->filesystemFactory, $this->settings, $this->url, $this->translator))->anonymize();
        }
    }

    private function sendUserConfirmation(string $mode, string $username, string $email, Mailer $mailer, TranslatorInterface $translator): void
    {
        $blueprint = new ErasureCompletedBlueprint($this->erasureRequest, $username, $mode);

        $mailer->send(
            $blueprint->getEmailViews(),
            $blueprint->getData(),
            function (Message $message) use ($username, $email, $blueprint, $translator) {
                $message->to($email, $username)
                    ->subject($blueprint->getEmailSubject($translator));
            }
        );
    }
}
