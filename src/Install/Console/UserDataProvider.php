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

    public function getDatabaseConfiguration()
    {
        $host = $this->ask('Database host:');
        $port = '3306';

        if (str_contains($host, ':')) {
            list($host, $port) = explode(':', $host, 2);
        }

        return [
            'driver'    => 'mysql',
            'host'      => $host,
            'port'      => $port,
            'database'  => $this->ask('Database name:'),
            'username'  => $this->ask('Database user:'),
            'password'  => $this->secret('Database password:'),
            'charset'   => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix'    => $this->ask('Prefix:'),
            'strict'    => false,
        ];
    }

    public function getBaseUrl()
    {
        return $this->baseUrl = rtrim($this->ask('Base URL:'), '/');
    }

    public function getAdminUser()
    {
        return [
            'username'              => $this->ask('Admin username:'),
            'password'              => $this->secret('Admin password:'),
            'password_confirmation' => $this->secret('Admin password (confirmation):'),
            'email'                 => $this->ask('Admin email address:'),
        ];
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

    public function isDebugMode(): bool
    {
        return false;
    }
}
