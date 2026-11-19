<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\user;

use Faker\Factory as FakerFactory;
use Faker\Generator;
use Faker\Provider\Base;
use Flarum\Extend\ExtenderInterface;
use Flarum\Extension\Extension;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use Illuminate\Contracts\Container\Container;
use PHPUnit\Framework\Attributes\Test;

class UserFactoryTest extends TestCase
{
    #[Test]
    public function factory_retries_a_repeated_username_and_email()
    {
        $faker = FakerFactory::create();
        $faker->addProvider(new class($faker) extends Base {
            private array $usernames = ['taken', 'taken', 'free'];
            private array $emails = ['taken@example.com', 'taken@example.com', 'free@example.com'];

            public function userName(): string
            {
                return array_shift($this->usernames);
            }

            public function safeEmail(): string
            {
                return array_shift($this->emails);
            }
        });

        $this->extend(new class($faker) implements ExtenderInterface {
            public function __construct(private Generator $faker)
            {
            }

            public function extend(Container $container, ?Extension $extension = null): void
            {
                $container->instance(Generator::class, $this->faker);
            }
        });

        $this->prepareDatabase([
            User::class => [['id' => 100], ['id' => 101]],
        ]);

        $users = $this->database()->table('users')->whereIn('id', [100, 101])->orderBy('id');

        $this->assertEquals(['taken', 'free'], $users->pluck('username')->all());
        $this->assertEquals(['taken@example.com', 'free@example.com'], $users->pluck('email')->all());
    }
}
