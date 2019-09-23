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
    protected $input;

    protected $output;

    protected $questionHelper;

    /** @var BaseUrl */
    protected $baseUrl;

    public function __construct(InputInterface $input, OutputInterface $output, QuestionHelper $questionHelper)
    {
        $this->input = $input;
        $this->output = $output;
        $this->questionHelper = $questionHelper;
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
        $host = $this->ask('Database host (required):');
        $port = 3306;

        if (Str::contains($host, ':')) {
            list($host, $port) = explode(':', $host, 2);
        }

        return new DatabaseConfig(
            'mysql',
            $host,
            intval($port),
            $this->ask('Database name (required):'),
            $this->ask('Database user (required):'),
            $this->secret('Database password:'),
            $this->ask('Prefix:')
        );
    }

    private function getBaseUrl(): BaseUrl
    {
        $baseUrl = $this->ask('Base URL (Default: http://flarum.local):', 'http://flarum.local');

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

    private function askForAdminPassword()
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

    private function getSettings()
    {
        $title = $this->ask('Forum title:');

        return [
            'forum_title' => $title,
            'mail_from' => $this->baseUrl->toEmail('noreply'),
            'welcome_title' => 'Welcome to '.$title,
        ];
    }

    private function ask($question, $default = null)
    {
        $question = new Question("<question>$question</question> ", $default);

        return $this->questionHelper->ask($this->input, $this->output, $question);
    }

    private function secret($question)
    {
        $question = new Question("<question>$question</question> ");

        $question->setHidden(true)->setHiddenFallback(true);

        return $this->questionHelper->ask($this->input, $this->output, $question);
    }

    private function validationError($message)
    {
        $this->output->writeln("<error>$message</error>");
        $this->output->writeln('Please try again.');
    }
}
