<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Api\Controller;

use Flarum\Foundation\Application;
use Flarum\Settings\TemporarySettingsRepository;
use Flarum\User\AssertPermissionTrait;
use Illuminate\Contracts\Validation\Factory;
use Illuminate\Support\Arr;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Swift_Mailer;
use Symfony\Component\Translation\TranslatorInterface;

class SendTestMailController implements RequestHandlerInterface
{
    use AssertPermissionTrait;

    protected $app;

    protected $translator;

    public function __construct(Application $app, TranslatorInterface $translator)
    {
        $this->app = $app;
        $this->translator = $translator;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->assertAdmin($request->getAttribute('actor'));

        $settings = $request->getParsedBody();

        $requiredSettings = ['mail_driver', 'mail_from', 'mail_test_recipient'];

        foreach ($requiredSettings as $setting) {
            if (! array_key_exists($setting, $settings)) {
                return $this->response([$this->translator->trans('core.email.send_test.missing_setting', ['setting' => $setting])], 400);
            }
        }

        $testRecipientEmail = Arr::get($settings, 'mail_test_recipient');

        if (! filter_var($testRecipientEmail, FILTER_VALIDATE_EMAIL)) {
            return $this->response([$this->translator->trans('core.email.send_test.invalid_recipient', ['email' => $testRecipientEmail])], 400);
        }

        $drivers = $this->app->make('mail.supported_drivers');
        $driverKey = Arr::get($settings, 'mail_driver');

        if (empty($drivers[$driverKey])) {
            return $this->response([$this->translator->trans('core.email.send_test.unsupported_driver', ['driver' => $driverKey])], 400);
        }

        $driver = $this->app->make($drivers[$driverKey]);

        $settingsRepository = new TemporarySettingsRepository();

        foreach ($driver->availableSettings() as $setting => $default) {
            if (array_key_exists($setting, $settings)) {
                $settingsRepository->set($setting, Arr::get($settings, $setting));
            } else {
                return $this->response([$this->translator->trans('core.email.send_test.missing_setting', ['setting' => $setting])], 400);
            }
        }

        $validator = $this->app->make(Factory::class);

        $errors = $driver->validate($settingsRepository, $validator);

        if (empty($errors)) {
            return $this->response($errors, 400);
        }

        $mailer = new Swift_Mailer($driver->buildTransport($settingsRepository));

        $message = $mailer->createMessage();

        $message->setSubject($this->translator->trans('core.email.send_test.subject'));
        $message->setSender(Arr::get($settings, 'mail_from'));
        $message->setFrom(Arr::get($settings, 'mail_from'));
        $message->setTo($testRecipientEmail);
        $message->setBody($this->translator->trans('core.email.send_test.body', ['username' => $request->getAttribute('actor')->usernamey]));

        $mailer->send($message);

        return $this->response([]);
    }

    private function response($message, $status = 200)
    {
        return new JsonResponse([
            'message' => $message
        ], $status);
    }
}
