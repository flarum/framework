<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Install\Console;

use Flarum\Install\AdminUser;
use Flarum\Install\BaseUrl;
use Flarum\Install\DatabaseConfig;
use Flarum\Install\Installation;
use Illuminate\Support\Str;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

class UserDataProvider implements DataProviderInterface
{
    protected BaseUrl $baseUrl;

    public function __construct(
        protected InputInterface $input,
        protected OutputInterface $output,
        protected QuestionHelper $questionHelper
    ) {
    }

    public function configure(Installation $installation): Installation
    {
        return $installation
            ->debugMode(false)
            ->baseUrl($this->getBaseUrl())
            ->databaseConfig($this->getDatabaseConfiguration())
            ->adminUser($this->getAdminUser())
            ->settings($this->getSettings());
    }

    private function getDatabaseConfiguration(): DatabaseConfig
    {
        $driver = $this->ask('Database driver (mysql, sqlite, pgsql) (Default: mysql):', 'mysql');
        $port = match ($driver) {
            'mysql' => 3306,
            'pgsql' => 5432,
            default => 0,
        };

        if (in_array($driver, ['mysql', 'pgsql'])) {
            $host = $this->ask('Database host (required):');

            if (Str::contains($host, ':')) {
                list($host, $port) = explode(':', $host, 2);
            }

            $user = $this->ask('Database user (required):');
            $password = $this->secret('Database password:');
        }

        return new DatabaseConfig(
            $driver,
            $host ?? null,
            intval($port),
            $this->ask('Database name (required):'),
            $user ?? null,
            $password ?? null,
            $this->ask('Prefix:')
        );
    }

    private function getBaseUrl(): BaseUrl
    {
        $baseUrl = $this->ask('Base URL (Default: http://flarum.localhost):', 'http://flarum.localhost');

        return $this->baseUrl = BaseUrl::fromString($baseUrl);
    }

    private function getAdminUser(): AdminUser
    {
        return new AdminUser(
            $this->ask('Admin username (Default: admin):', 'admin'),
            $this->askForAdminPassword(),
            $this->ask('Admin email address (required):')
        );
    }

    private function askForAdminPassword(): string
    {
        while (true) {
            $password = $this->secret('Admin password (required >= 8 characters):');

            if (strlen($password) < 8) {
                $this->validationError('Password must be at least 8 characters.');
                continue;
            }

            $confirmation = $this->secret('Admin password (confirmation):');

            if ($password !== $confirmation) {
                $this->validationError('The password did not match its confirmation.');
                continue;
            }

            return $password;
        }
    }

    private function getSettings(): array
    {
        $title = $this->ask('Forum title:');

        return [
            'forum_title' => $title,
            'mail_from' => $this->baseUrl->toEmail('noreply'),
            'welcome_title' => 'Welcome to '.$title,
        ];
    }

    private function ask(string $question, string $default = null): mixed
    {
        $question = new Question("<question>$question</question> ", $default);

        return $this->questionHelper->ask($this->input, $this->output, $question);
    }

    private function secret(string $question): mixed
    {
        $question = new Question("<question>$question</question> ");

        $question->setHidden(true)->setHiddenFallback(true);

        return $this->questionHelper->ask($this->input, $this->output, $question);
    }

    private function validationError(string $message): void
    {
        $this->output->writeln("<error>$message</error>");
        $this->output->writeln('Please try again.');
    }
}
