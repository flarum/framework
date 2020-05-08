<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Database;

use Flarum\Foundation\Paths;
use Illuminate\Filesystem\Filesystem;

class MigrationCreator
{
    /**
     * The filesystem instance.
     *
     * @var Filesystem
     */
    protected $files;

    /**
     * @var Paths
     */
    protected $paths;

    /**
     * Create a new migrator instance.
     *
     * @param Filesystem $files
     * @param Paths $paths
     */
    public function __construct(Filesystem $files, Paths $paths)
    {
        $this->files = $files;
        $this->paths = $paths;
    }

    /**
     * Create a new migration for the given extension.
     *
     * @param string $name
     * @param string $extension
     * @param string $table
     * @param bool $create
     * @return string
     */
    public function create($name, $extension = null, $table = null, $create = false)
    {
        $migrationPath = $this->getMigrationPath($extension);

        $path = $this->getPath($name, $migrationPath);

        $stub = $this->getStub($table, $create);

        $this->files->put($path, $this->populateStub($stub, $table));

        return $path;
    }

    /**
     * Get the migration stub file.
     *
     * @param string $table
     * @param bool $create
     * @return string
     */
    protected function getStub($table, $create)
    {
        if (is_null($table)) {
            return $this->files->get($this->getStubPath().'/blank.stub');
        }

        // We also have stubs for creating new tables and modifying existing tables
        // to save the developer some typing when they are creating a new tables
        // or modifying existing tables. We'll grab the appropriate stub here.
        $stub = $create ? 'create.stub' : 'update.stub';

        return $this->files->get($this->getStubPath()."/{$stub}");
    }

    /**
     * Populate the place-holders in the migration stub.
     *
     * @param string $stub
     * @param string $table
     * @return string
     */
    protected function populateStub($stub, $table)
    {
        $replacements = [
            '{{table}}' => $table
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $stub);
    }

    /**
     * Get the full path name to the migration directory.
     *
     * @param string $extension
     * @return string
     */
    protected function getMigrationPath($extension)
    {
        if ($extension) {
            return $this->paths->vendor.'/'.$extension.'/migrations';
        } else {
            return __DIR__.'/../../migrations';
        }
    }

    /**
     * Get the full path name to the migration.
     *
     * @param string $name
     * @param string $path
     * @return string
     */
    protected function getPath($name, $path)
    {
        return $path.'/'.$this->getDatePrefix().'_'.$name.'.php';
    }

    /**
     * Get the date prefix for the migration.
     *
     * @return string
     */
    protected function getDatePrefix()
    {
        return date('Y_m_d_His');
    }

    /**
     * Get the path to the stubs.
     *
     * @return string
     */
    protected function getStubPath()
    {
        return __DIR__.'/../../stubs/migrations';
    }
}
