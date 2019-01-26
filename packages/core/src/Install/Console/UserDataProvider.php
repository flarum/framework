<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Install\Console;

use Flarum\Install\AdminUser;
use Flarum\Install\DatabaseConfig;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

class UserDataProvider implements DataProviderInterface
{
    protected $input;

    protected $output;

    protected $questionHelper;

    protected $baseUrl;

    public function __construct(InputInterface $input, OutputInterface $output, QuestionHelper $questionHelper)
    {
        $this->input = $input;
        $this->output = $output;
        $this->questionHelper = $questionHelper;
    }

    public function getDatabaseConfiguration(): DatabaseConfig
    {
        $host = $this->ask('Database host:');
        $port = 3306;

        if (str_contains($host, ':')) {
            list($host, $port) = explode(':', $host, 2);
        }

        return new DatabaseConfig(
            'mysql',
            $host,
            intval($port),
            $this->ask('Database name:'),
            $this->ask('Database user:'),
            $this->secret('Database password:'),
            $this->ask('Prefix:')
        );
    }

    public function getBaseUrl()
    {
        return $this->baseUrl = rtrim($this->ask('Base URL:'), '/');
    }

    public function getAdminUser(): AdminUser
    {
        return new AdminUser(
            $this->ask('Admin username:'),
            $this->askForAdminPassword(),
            $this->ask('Admin email address:')
        );
    }

    private function askForAdminPassword()
    {
        while (true) {
            $password = $this->secret('Admin password:');

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

    public function getSettings()
    {
        $title = $this->ask('Forum title:');
        $baseUrl = $this->baseUrl ?: 'http://localhost';

        return [
            'forum_title' => $title,
            'mail_from' => 'noreply@'.preg_replace('/^www\./i', '', parse_url($baseUrl, PHP_URL_HOST)),
            'welcome_title' => 'Welcome to '.$title,
        ];
    }

    protected function ask($question, $default = null)
    {
        $question = new Question("<question>$question</question> ", $default);

        return $this->questionHelper->ask($this->input, $this->output, $question);
    }

    protected function secret($question)
    {
        $question = new Question("<question>$question</question> ");

        $question->setHidden(true)->setHiddenFallback(true);

        return $this->questionHelper->ask($this->input, $this->output, $question);
    }

    protected function validationError($message)
    {
        $this->output->writeln("<error>$message</error>");
        $this->output->writeln('Please try again.');
    }

    public function isDebugMode(): bool
    {
        return false;
    }
}
