<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\api\access_tokens;

use Flarum\Testing\integration\TestCase;
use Illuminate\Contracts\Config\Repository;
use PHPUnit\Framework\Attributes\Test;
use SessionHandlerInterface;

/**
 * The session lifetime has to reach the session handler as well as the cookie:
 * the handler decides, server-side, whether a session it is handed is still
 * alive. Handlers read it from Laravel's `session.lifetime` config, including
 * those an extension binds itself, so that is where the configured value goes.
 */
class SessionHandlerLifetimeTest extends TestCase
{
    /**
     * A session last used the given number of minutes ago, as the default
     * (file) handler stores it.
     */
    protected function sessionIdleFor(int $minutes): string
    {
        $container = $this->app()->getContainer();

        /** @var SessionHandlerInterface $handler */
        $handler = $container->make(SessionHandlerInterface::class);
        $id = 'lifetime-test-'.$minutes;

        $handler->write($id, 'still here');
        touch($container->make(Repository::class)->get('session.files').'/'.$id, time() - $minutes * 60);

        return $handler->read($id);
    }

    #[Test]
    public function the_handler_keeps_a_session_for_a_lengthened_lifetime()
    {
        $this->setting('session.lifetime', 300);

        $this->assertEquals('still here', $this->sessionIdleFor(200));
    }

    #[Test]
    public function the_handler_discards_a_session_past_a_shortened_lifetime()
    {
        $this->setting('session.lifetime', 30);

        $this->assertEquals('', $this->sessionIdleFor(60));
    }

    #[Test]
    public function the_handler_follows_config_php_over_the_setting()
    {
        $this->config('session.lifetime', 30);
        $this->setting('session.lifetime', 300);

        $this->assertEquals('', $this->sessionIdleFor(60));
    }

    #[Test]
    public function the_handler_keeps_the_default_when_nothing_is_configured()
    {
        $this->assertEquals('still here', $this->sessionIdleFor(100));
        $this->assertEquals('', $this->sessionIdleFor(150));
    }
}
