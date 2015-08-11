<?php namespace Flarum\Install\Console;

use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

class DataFromUser implements ProvidesData
{
    protected $input;

    protected $output;

    protected $questionHelper;


    public function __construct(InputInterface $input, OutputInterface $output, QuestionHelper $questionHelper)
    {
        $this->input = $input;
        $this->output = $output;
        $this->questionHelper = $questionHelper;
    }

    public function getDatabaseConfiguration()
    {
        return [
            'driver'   => 'mysql',
            'host'     => $this->ask('Database host:'),
            'database' => $this->ask('Database name:'),
            'username' => $this->ask('Database user:'),
            'password' => $this->secret('Database password:'),
            'prefix'   => $this->ask('Prefix:'),
        ];
    }

    public function getAdminUser()
    {
        return [
            'username' => $this->ask('Admin username:'),
            'password' => $this->secret('Admin password:'),
            'email'    => $this->ask('Admin email address:'),
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
}
