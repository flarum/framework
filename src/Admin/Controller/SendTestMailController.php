<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Admin\Controller;

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

class SendTestMailController implements RequestHandlerInterface
{
    use AssertPermissionTrait;

    protected static $container;

    protected $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->assertAdmin($request->getAttribute('actor'));

        $settings = $request->getParsedBody();

        $requiredSettings = ['mail_driver', 'mail_from', 'mail_test_recipient'];

        foreach ($requiredSettings as $setting) {
            if (! array_key_exists($setting, $settings)) {
                return $this->response(["Required setting '$setting' is missing."], 400);
            }
        }

        $testRecipientEmail = Arr::get($settings, 'mail_test_recipient');

        if (!filter_var($testRecipientEmail, FILTER_VALIDATE_EMAIL)) {
            return $this->response(["Provided test recipient address, $testRecipientEmail, is not a valid email."], 400);
        }

        $drivers = $this->app->make('mail.supported_drivers');
        $driverKey = Arr::get($settings, 'mail_driver');

        if (empty($drivers[$driverKey])) {
            return $this->response(["Unsupported driver: '$driverKey'"], 400);
        }

        $driver = $this->app->make($drivers[$driverKey]);

        $settingsRepository = new TemporarySettingsRepository();

        foreach ($driver->availableSettings() as $setting => $default) {
            if (array_key_exists($setting, $settings)) {
                $settingsRepository->set($setting, Arr::get($settings, $setting));
            } else {
                return $this->response(["Required setting '$setting' is missing."], 400);
            }
        }

        $validator = $this->app->make(Factory::class);

        $errors = $driver->validate($settingsRepository, $validator);

        if (empty($errors)) {
            return $this->response($errors, 400);
        }

        $mailer = new Swift_Mailer($driver->buildTransport($settingsRepository));

        $message = $mailer->createMessage();

        $message->setSubject('Flarum Email Test');
        $message->setSender(Arr::get($settings, 'mail_from'));
        $message->setFrom(Arr::get($settings, 'mail_from'));
        $message->setTo($testRecipientEmail);
        $message->setBody("Hello,\n\nThis is a test email to confirm that your Flarum email configuration is working properly. Have a great day!\n\nBest,\nFlarum Mail Bot");

        $mailer->send($message);

        return $this->response(['Success']);
    }

    private function response($message, $status = 200)
    {
        return new JsonResponse([
            "message" => $message
        ], $status);
    }
}
