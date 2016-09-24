<?php
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Console\Command;

use Exception;
use Flarum\Foundation\Application;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Question\Question;

class GenerateExtensionCommand extends AbstractCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('generate:extension')
            ->setDescription('Generate a Flarum extension skeleton')
            ->addOption(
                'name',
                null,
                InputOption::VALUE_OPTIONAL,
                'Extension name'
            )
            ->addOption(
                'author',
                null,
                InputOption::VALUE_OPTIONAL,
                'Author name'
            )
            ->addOption(
                'email',
                null,
                InputOption::VALUE_OPTIONAL,
                'Author email'
            )
            ->addOption(
                'vendor',
                null,
                InputOption::VALUE_OPTIONAL,
                'Vendor name'
            )
            ->addOption(
                'package',
                null,
                InputOption::VALUE_OPTIONAL,
                'Composer package name'
            )
            ->addOption(
                'namespace',
                null,
                InputOption::VALUE_OPTIONAL,
                'Extension Namespace'
            )
            ->addOption(
                'title',
                null,
                InputOption::VALUE_OPTIONAL,
                'Extension title'
            )
            ->addOption(
                'description',
                null,
                InputOption::VALUE_OPTIONAL,
                'Extension description'
            )
            ->addOption(
                'license',
                null,
                InputOption::VALUE_OPTIONAL,
                'Extension license'
            )
            ->addOption(
                'nogit',
                false,
                InputOption::VALUE_NONE,
                'Do not try to get information from git'
            )
            ->addOption(
                'nojs',
                false,
                InputOption::VALUE_NONE,
                'Do not build JavaScript dependencies'
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function fire()
    {
        $git = ! $this->input->getOption('nogit') ? $this->getGitConfig() : [];

        $name = strtolower($this->getOptionOrAsk(
            'name',
            'Extension name',
            function ($input) {
                return preg_match('/^([a-z0-9-]+)$/i', $input);
            }
        ));

        $authorName = $this->getOptionOrAsk(
            'author',
            'Author name',
            function ($input) {
                return preg_match('/^([\w\s]+)$/i', $input);
            },
            array_get($git, 'user.name')
        );

        $authorEmail = $this->getOptionOrAsk(
            'email',
            'Author email',
            function ($input) {
                return filter_var($input, FILTER_VALIDATE_EMAIL);
            },
            array_get($git, 'user.email')
        );

        $vendor = strtolower($this->getOptionOrAsk(
            'vendor',
            'Vendor name',
            function ($input) {
                return preg_match('/^([a-z0-9-]+)$/i', $input);
            },
            str_replace(' ', '', $authorName)
        ));

        $packageName = strtolower($this->getOptionOrAsk(
            'package',
            'Package name (<vendor>/<name>)',
            function ($input) {
                return preg_match('/^([a-z0-9_\.\-]+\/[a-z0-9_\.\-]+)$/i', $input);
            },
            $vendor.'/flarum-ext-'.$name
        ));

        $namespace = $this->getOptionOrAsk(
            'namespace',
            'Namespace',
            function ($input) {
                return preg_match('/^([a-z0-9_\\\\]+)$/i', $input);
            },
            ucfirst($vendor).'\\'.ucfirst($name)
        );

        $title = $this->getOptionOrAsk(
            'title',
            'Extension Title',
            function ($input) {
                return ! empty($input);
            },
            ucfirst($name)
        );

        $description = $this->getOptionOrAsk(
            'description',
            'Description',
            function ($input) {
                return true;
            }
        );

        $license = $this->getOptionOrAsk(
            'license',
            'License',
            function ($input) {
                return true;
            }
        );

        $package = $vendor.'-'.$name;

        $dir = public_path().'/workbench/'.$package;

        if (is_dir($dir)) {
            throw new Exception($dir.' folder already exists');
        }

        $composerPath = public_path().'/composer.json';

        $composer = json_decode(file_get_contents($composerPath), true);

        if (array_has($composer, 'require.'.$packageName)) {
            throw new Exception($packageName.' already exists in composer.json');
        }

        $this->info('Generating extension skeleton for "'.$package.'"...');

        $replacements = [
            '{{namespace}}' => $namespace,
            '{{name}}' => $package,
        ];

        $this->copyStub($dir, $replacements);

        $manifest = [
            'name' => $packageName,
            'description' => $description,
            'type' => 'flarum-extension',
            'keywords' => [],
            'license' => $license,
            'authors' => [
                [
                    'name' => $authorName,
                    'email' => $authorEmail,
                ],
            ],
            'require' => [
                'flarum/core' => '^'.Application::VERSION,
            ],
            'autoload' => [
                'psr-4' => [
                    $namespace.'\\' => 'src/',
                ],
            ],
            'extra' => [
                'flarum-extension' => [
                    'title' => $title,
                    'icon' => [
                        'name' => '',
                        'backgroundColor' => '',
                        'color' => '',
                    ],
                ],
            ],
        ];

        file_put_contents($dir.'/composer.json', json_encode($manifest, JSON_PRETTY_PRINT));

        if (! $this->input->getOption('nojs')) {
            $this->info('Downloading JavaScript dependencies');

            passthru('cd '.escapeshellarg($dir).'; cd js/forum; npm install; gulp default; cd ../admin; npm install; gulp default');
        }

        $this->info('Updating Composer');

        if (! isset($composer['repositories'])) {
            $composer['repositories'] = [
                [
                    'type' => 'path',
                    'url' => 'workbench\/*\/',
                ],
            ];
        }

        $composer['require'][$packageName] = 'dev-master';

        file_put_contents($composerPath, json_encode($composer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        passthru('cd '.escapeshellarg(public_path()).'; composer update');

        $this->getApplication()->find('cache:clear')->run($this->input, $this->output);

        $this->info('Extension "'.$package.'" generated!');
    }

    /**
     * Returns option value otherwise ask user to input.
     *
     * @param string $question
     * @param callable $checkValid
     * @param string|null $default
     * @return string
     */
    protected function getOptionOrAsk($option, $question, callable $checkValid, $default = null)
    {
        $input = $this->input->getOption($option);

        if ($input) {
            if (! $checkValid($input)) {
                throw new Exception($option.' option is not valid');
            }

            return $input;
        }

        do {
            $input = $this->ask($question, $default);
        } while (! $checkValid($input));

        return $input;
    }

    /**
     * Ask user to input string.
     *
     * @param string $question
     * @param string|null $default
     * @return string
     */
    protected function ask($question, $default = null)
    {
        $question = new Question(
            '<question>'.$question.($default ? ' <comment>['.$default.']</comment>' : '').':</question> ',
            $default
        );

        return $this->getHelperSet()->get('question')->ask($this->input, $this->output, $question);
    }

    /**
     * Copy extension skeleton to destination.
     *
     * @param string $destination
     * @param array $replacements
     * @return void
     */
    protected function copyStub($destination, $replacements = [])
    {
        $this->recursiveCopy(__DIR__.'/../../../stubs/extension', $destination, $replacements);
    }

    /**
     * Copy source to destination.
     *
     * @param string $src
     * @param string $dst
     * @param array $replacements
     * @return void
     */
    protected function recursiveCopy($src, $dst, $replacements = [])
    {
        $dir = opendir($src);
        @mkdir($dst, 0775, true);

        while (($file = readdir($dir)) !== false) {
            if ($file != '.' && $file != '..') {
                if (is_dir($src.'/'.$file)) {
                    $this->recursiveCopy($src.'/'.$file, $dst.'/'.$file, $replacements);
                } else {
                    $contents = str_replace(
                        array_keys($replacements), array_values($replacements),
                        file_get_contents($src.'/'.$file)
                    );
                    file_put_contents($dst.'/'.$file, $contents);
                }
            }
        }

        closedir($dir);
    }

    /**
     * Get git config information.
     *
     * @return array
     */
    protected function getGitConfig()
    {
        $info = $output = [];

        $status = -1;

        exec('git config -l', $output, $status);

        if ($status === 0) {
            foreach ($output as $line) {
                list($key, $value) = explode('=', $line);
                $info[$key] = $value;
            }
        }

        return $info;
    }
}
