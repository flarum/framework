<?php
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Console;

use Illuminate\Contracts\Container\Container;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Question\Question;
use Flarum\Core\Application;

class GenerateExtensionCommand extends Command
{
    /**
     * @var Container
     */
    protected $container;

    public function __construct(Container $container)
    {
        $this->container = $container;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('generate:extension')
            ->setDescription("Generate a Flarum extension skeleton.");
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    protected function fire()
    {
        do {
            $name = $this->ask('Extension name:');
        } while (! preg_match('/^([a-z0-9-]+)$/i', $name));

        do {
            $namespace = $this->ask('Namespace:');
        } while (! preg_match('/^([a-z0-9_\\\\]+)$/i', $namespace));

        do {
            $title = $this->ask('Title:');
        } while (! $title);

        $description = $this->ask('Description:');

        $authorName = $this->ask('Author name:');

        $authorEmail = $this->ask('Author email:');

        $license = $this->ask('License:');

        $this->info('Generating extension skeleton for "'.$name.'"...');

        $dir = public_path().'/extensions/'.$name;

        $replacements = [
            '{{namespace}}' => $namespace,
            '{{escapedNamespace}}' => str_replace('\\', '\\\\', $namespace),
            '{{name}}' => $name
        ];

        $this->copyStub($dir, $replacements);

        $manifest = [
            'name' => $name,
            'title' => $title,
            'description' => $description,
            'keywords' => [],
            'version' => '0.1.0',
            'author' => [
                'name' => $authorName,
                'email' => $authorEmail,
                'homepage' => ''
            ],
            'license' => $license,
            'require' => [
                'flarum' => '>'.Application::VERSION
            ],
            'icon' => [
                'name' => '',
                'backgroundColor' => '',
                'color' => ''
            ]
        ];

        file_put_contents($dir.'/flarum.json', json_encode($manifest, JSON_PRETTY_PRINT));

        passthru("cd $dir; composer install; cd js/forum; npm install; gulp; cd ../admin; npm install; gulp");

        $this->info('Extension "'.$name.'" generated!');
    }

    protected function ask($question, $default = null)
    {
        $question = new Question("<question>$question</question> ", $default);

        return $this->getHelperSet()->get('question')->ask($this->input, $this->output, $question);
    }

    protected function copyStub($destination, $replacements = [])
    {
        $this->recursiveCopy(__DIR__.'/../../stubs/extension', $destination, $replacements);
    }

    protected function recursiveCopy($src, $dst, $replacements = [])
    {
        $dir = opendir($src);
        @mkdir($dst);

        while (($file = readdir($dir)) !== false) {
            if ($file != '.' && $file != '..') {
                if (is_dir($src.'/'.$file)) {
                    $this->recursiveCopy($src.'/'.$file, $dst.'/'.$file, $replacements);
                } else {
                    $contents = file_get_contents($src.'/'.$file);
                    $contents = str_replace(array_keys($replacements), array_values($replacements), $contents);

                    file_put_contents($dst.'/'.$file, $contents);
                }
            }
        }

        closedir($dir);
    }
}
