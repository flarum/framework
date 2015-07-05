<?php namespace Flarum\Console;

use Illuminate\Console\Command;
use Illuminate\Foundation\Application;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class GenerateExtensionCommand extends Command
{

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'generate:extension';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate a Flarum extension skeleton.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(Application $app)
    {
        parent::__construct();

        $this->app = $app;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function fire()
    {
        do {
            $name = $this->ask('Extension name (<vendor>-<name>):');
        } while (! preg_match('/^([a-z0-9]+)-([a-z0-9-]+)$/i', $name, $match));

        list(, $vendor, $package) = $match;

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
            '{{namespace}}' => ucfirst($vendor).'\\'.ucfirst($package),
            '{{escapedNamespace}}' => ucfirst($vendor).'\\\\'.ucfirst($package),
            '{{classPrefix}}' => ucfirst($package),
            '{{name}}' => $name
        ];

        $this->copyStub($dir, $replacements);

        rename($dir.'/src/ServiceProvider.php', $dir.'/src/'.ucfirst($package).'ServiceProvider.php');

        $manifest = [
            'name' => $name,
            'title' => $title,
            'description' => $description,
            'tags' => [],
            'version' => '0.1.0',
            'author' => [
                'name' => $authorName,
                'email' => $authorEmail
            ],
            'license' => $license,
            'require' => [
                'php' => '>=5.4.0',
                'flarum' => '>0.1.0'
            ]
        ];

        file_put_contents($dir.'/flarum.json', json_encode($manifest, JSON_PRETTY_PRINT));

        passthru("cd $dir; composer install; cd js; npm install; gulp");

        $this->info('Extension "'.$name.'" generated!');
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
