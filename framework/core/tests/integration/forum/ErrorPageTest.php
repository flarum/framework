<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\forum;

use Flarum\Extend;
use Flarum\Foundation\ErrorHandling\HandledError;
use Flarum\Foundation\ErrorHandling\ViewFormatter;
use Flarum\Http\Middleware\AuthenticateWithSession;
use Flarum\Http\RequestUtil;
use Flarum\Notification\UnsubscribeToken;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\EmailToken;
use Flarum\User\Guest;
use Flarum\User\PasswordToken;
use Flarum\User\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Laminas\Diactoros\ServerRequest;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Error pages (and the other standalone pages sharing their layout) load none
 * of the forum's assets, so they follow its color scheme and show its favicon
 * on their own.
 *
 * Tests run in debug mode, where the forum shows Whoops instead, so the page is
 * rendered through the formatter that serves it in production.
 */
class ErrorPageTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setting('theme_secondary_color', '#536F90');
    }

    private function renderResponse(?ServerRequest $request = null): ResponseInterface
    {
        /** @var ViewFormatter $formatter */
        $formatter = $this->app()->getContainer()->make(ViewFormatter::class);

        $error = new HandledError((new ModelNotFoundException())->setModel('Test'), 'not_found', 404);

        return $formatter->format($error, $request ?? new ServerRequest());
    }

    private function render(?ServerRequest $request = null): string
    {
        return (string) $this->renderResponse($request)->getBody();
    }

    #[Test]
    public function error_page_follows_the_system_color_scheme_when_the_forum_does(): void
    {
        $this->setting('color_scheme', 'auto');

        $html = $this->render();

        $this->assertStringContainsString('@media (prefers-color-scheme: dark)', $html);
        $this->assertStringContainsString('--body-bg: hsl(212,20%,10%);', $html);
    }

    #[Test]
    public function error_page_is_dark_when_the_forum_is(): void
    {
        $this->setting('color_scheme', 'dark');

        $html = $this->render();

        $this->assertStringContainsString('@media all', $html);
        $this->assertStringContainsString('--body-bg: hsl(212,20%,10%);', $html);
        $this->assertStringNotContainsString('prefers-color-scheme', $html);
    }

    #[Test]
    public function error_page_is_dark_when_the_forum_is_dark_high_contrast(): void
    {
        $this->setting('color_scheme', 'dark-hc');

        $html = $this->render();

        $this->assertStringContainsString('@media all', $html);
        $this->assertStringNotContainsString('prefers-color-scheme', $html);
    }

    #[Test]
    public function error_page_is_light_when_the_forum_is(): void
    {
        $this->setting('color_scheme', 'light');

        $html = $this->render();

        $this->assertStringNotContainsString('@media', $html);
        $this->assertStringNotContainsString('color-scheme: dark', $html);
    }

    #[Test]
    public function error_page_shows_the_forum_favicon(): void
    {
        $this->setting('favicon_path', 'favicon-abc123.png');

        $this->assertStringContainsString(
            '<link rel="shortcut icon" href="http://localhost/assets/favicon-abc123.png">',
            $this->render()
        );
    }

    #[Test]
    public function error_page_has_no_favicon_when_the_forum_has_none(): void
    {
        $this->assertStringNotContainsString('rel="shortcut icon"', $this->render());
    }

    #[Test]
    public function error_page_is_marked_as_guest_for_a_guest(): void
    {
        $request = RequestUtil::withActor(new ServerRequest(), new Guest());

        $this->assertStringContainsString('<html class="guest-user">', $this->render($request));
    }

    #[Test]
    public function error_page_is_marked_as_logged_in_for_a_user(): void
    {
        $this->prepareDatabase([
            'users' => [$this->normalUser()],
        ]);

        $this->app();

        $request = RequestUtil::withActor(new ServerRequest(), User::findOrFail(2));

        $this->assertStringContainsString('<html class="logged-in">', $this->render($request));
    }

    #[Test]
    public function error_page_has_no_actor_class_without_an_actor(): void
    {
        $this->assertStringContainsString('<html>', $this->render());
    }

    #[Test]
    public function error_page_is_private_and_not_reusable(): void
    {
        $response = $this->renderResponse();

        $this->assertEquals('private, no-cache', $response->getHeaderLine('Cache-Control'));
    }

    private function htmlRequest(string $method, string $path, array $options = [])
    {
        return $this->request($method, $path, $options)->withHeader('Accept', 'text/html');
    }

    #[Test]
    public function not_found_page_is_marked_as_logged_in_through_the_middleware_pipeline(): void
    {
        $this->prepareDatabase(['users' => [$this->normalUser()]]);

        $response = $this->send($this->htmlRequest('GET', '/does-not-exist', ['authenticatedAs' => 2]));

        $this->assertEquals(404, $response->getStatusCode());
        $this->assertStringContainsString('<html class="logged-in">', (string) $response->getBody());
        $this->assertEquals('private, no-cache', $response->getHeaderLine('Cache-Control'));
    }

    #[Test]
    public function not_found_page_is_marked_as_guest_through_the_middleware_pipeline(): void
    {

        $response = $this->send($this->htmlRequest('GET', '/does-not-exist'));

        $this->assertEquals(404, $response->getStatusCode());
        $this->assertStringContainsString('<html class="guest-user">', (string) $response->getBody());
    }

    #[Test]
    public function csrf_mismatch_page_is_marked_with_the_actor(): void
    {
        $this->prepareDatabase(['users' => [$this->normalUser()]]);

        // A session login, so the request is cookie-authenticated and CSRF-checked.
        $login = $this->send($this->requestWithCsrfToken(
            $this->request('POST', '/login', ['json' => ['identification' => 'normal', 'password' => 'too-obscure']])
        ));
        $this->assertEquals(200, $login->getStatusCode());

        $response = $this->send($this->htmlRequest('POST', '/logout', ['cookiesFrom' => $login]));

        $this->assertEquals(400, $response->getStatusCode());
        $this->assertEquals('private, no-cache', $response->getHeaderLine('Cache-Control'));
        $this->assertStringContainsString('<html class="logged-in">', (string) $response->getBody());

        $response = $this->send($this->htmlRequest('POST', '/logout'));

        $this->assertEquals(400, $response->getStatusCode());
        $this->assertStringContainsString('<html class="guest-user">', (string) $response->getBody());
    }

    #[Test]
    public function maintenance_page_is_marked_with_the_actor(): void
    {
        $this->setting('maintenance_mode', 'low');
        $this->prepareDatabase(['users' => [$this->normalUser()]]);

        $response = $this->send($this->htmlRequest('GET', '/', ['authenticatedAs' => 2]));

        $this->assertEquals(503, $response->getStatusCode());
        $this->assertEquals('private, no-cache', $response->getHeaderLine('Cache-Control'));
        $this->assertStringContainsString('<html class="logged-in">', (string) $response->getBody());

        $response = $this->send($this->htmlRequest('GET', '/'));

        $this->assertEquals(503, $response->getStatusCode());
        $this->assertStringContainsString('<html class="guest-user">', (string) $response->getBody());
    }

    #[Test]
    public function log_out_page_is_marked_as_logged_in(): void
    {
        $this->prepareDatabase(['users' => [$this->normalUser()]]);

        $response = $this->send($this->htmlRequest('GET', '/logout', ['authenticatedAs' => 2]));

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertStringContainsString('<html class="logged-in">', (string) $response->getBody());
    }

    #[Test]
    public function maintenance_log_in_page_is_marked_as_guest(): void
    {
        $this->setting('maintenance_mode', 'low');

        $response = $this->send($this->htmlRequest('GET', '/maintenance/login'));

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertStringContainsString('<html class="guest-user">', (string) $response->getBody());
    }

    private function assertPageClass(string $class, ResponseInterface $response, int $status = 200): void
    {
        $this->assertEquals($status, $response->getStatusCode());
        $this->assertStringContainsString('<html class="'.$class.'">', (string) $response->getBody());
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function actorProvider(): array
    {
        return ['guest' => ['guest-user'], 'user' => ['logged-in']];
    }

    private function actorOptions(string $class): array
    {
        return $class === 'logged-in' ? ['authenticatedAs' => 2] : [];
    }

    #[Test]
    #[DataProvider('actorProvider')]
    public function confirm_email_page_is_marked_with_the_actor(string $class): void
    {
        $this->prepareDatabase(['users' => [$this->normalUser()]]);
        $this->app();

        $token = EmailToken::generate('normal@machine.local', 2);
        $token->save();

        $response = $this->send($this->htmlRequest('GET', '/confirm/'.$token->token, $this->actorOptions($class)));

        $this->assertPageClass($class, $response);
    }

    #[Test]
    #[DataProvider('actorProvider')]
    public function reset_password_page_is_marked_with_the_actor(string $class): void
    {
        $this->prepareDatabase(['users' => [$this->normalUser()]]);
        $this->app();

        $token = PasswordToken::generate(2);
        $token->save();

        $response = $this->send($this->htmlRequest('GET', '/reset/'.$token->token, $this->actorOptions($class)));

        $this->assertPageClass($class, $response);
    }

    #[Test]
    #[DataProvider('actorProvider')]
    public function unsubscribe_confirmation_page_is_marked_with_the_actor(string $class): void
    {
        $this->prepareDatabase(['users' => [$this->normalUser()]]);
        $this->app();

        $token = UnsubscribeToken::generate(2, 'newPost');
        $token->save();

        $response = $this->send($this->htmlRequest('GET', '/notifications/unsubscribe/2/'.$token->token, $this->actorOptions($class)));

        $this->assertPageClass($class, $response);
        $this->assertStringContainsString('unsubscribe', strtolower((string) $response->getBody()));
    }

    #[Test]
    #[DataProvider('actorProvider')]
    public function unsubscribe_error_page_is_marked_with_the_actor(string $class): void
    {
        $this->prepareDatabase(['users' => [$this->normalUser()]]);

        $response = $this->send($this->htmlRequest('GET', '/notifications/unsubscribe/2/invalid-token', $this->actorOptions($class)));

        $this->assertPageClass($class, $response);
    }

    #[Test]
    #[DataProvider('actorProvider')]
    public function admin_error_page_is_marked_with_the_actor_and_not_stored(string $class): void
    {
        $this->prepareDatabase(['users' => [$this->normalUser()]]);

        $options = $class === 'logged-in' ? ['authenticatedAs' => 1] : [];
        $response = $this->send($this->htmlRequest('GET', '/admin/does-not-exist', $options));

        $this->assertEquals(404, $response->getStatusCode());
        $this->assertStringContainsString('<html class="'.$class.'">', (string) $response->getBody());
        $this->assertEquals('max-age=0, no-store', $response->getHeaderLine('Cache-Control'));
    }

    #[Test]
    public function error_before_session_authentication_is_marked_as_guest_for_a_signed_in_visitor(): void
    {
        $this->extend(
            (new Extend\Middleware('forum'))->insertBefore(AuthenticateWithSession::class, FailsBeforeAuthentication::class)
        );
        $this->prepareDatabase(['users' => [$this->normalUser()]]);

        $login = $this->send($this->requestWithCsrfToken(
            $this->request('POST', '/login', ['json' => ['identification' => 'normal', 'password' => 'too-obscure']])
        ));
        $this->assertEquals(200, $login->getStatusCode());

        $response = $this->send($this->htmlRequest('GET', '/early-error', ['cookiesFrom' => $login]));

        $this->assertPageClass('guest-user', $response, 404);
    }
}

class FailsBeforeAuthentication implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if ($request->getUri()->getPath() === '/early-error') {
            throw (new ModelNotFoundException())->setModel('Test');
        }

        return $handler->handle($request);
    }
}
