<?php namespace Flarum\Support;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use Cache;
use Less_Parser;
use Closure;

class AssetManager
{
    protected $files = [
        'css' => [],
        'js' => [],
        'less' => []
    ];

    protected $less = [];

    protected $publicPath;

    protected $name;

    protected $storage;

    public function __construct(Filesystem $storage, $publicPath, $name)
    {
        $this->storage = $storage;
        $this->publicPath = $publicPath;
        $this->name = $name;
    }

    public function addFile($files)
    {
        foreach ((array) $files as $file) {
            $ext = pathinfo($file, PATHINFO_EXTENSION);
            $this->files[$ext][] = $file;
        }
    }

    public function addLess($strings)
    {
        foreach ((array) $strings as $string) {
            $this->less[] = $string;
        }
    }

    protected function getAssetDirectory()
    {
        $dir = $this->publicPath;
        if (! $this->storage->isDirectory($dir)) {
            $this->storage->makeDirectory($dir);
        }
        return $dir;
    }

    protected function getRevisionFile()
    {
        return $this->getAssetDirectory().'/'.$this->name;
    }

    protected function getRevision()
    {
        if (file_exists($file = $this->getRevisionFile())) {
            return file_get_contents($file);
        }
    }

    protected function putRevision($revision)
    {
        return file_put_contents($this->getRevisionFile(), $revision);
    }

    protected function getFiles($type, Closure $callback)
    {
        $dir = $this->getAssetDirectory();

        if (! ($revision = $this->getRevision())) {
            $revision = Str::quickRandom();
            $this->putRevision($revision);
        }

        if (! file_exists($file = $dir.'/'.$this->name.'-'.$revision.'.'.$type)) {
            $this->storage->put($file, $callback());
        }

        return [$file];
    }

    public function clearCache()
    {
        if ($revision = $this->getRevision()) {
            $dir = $this->getAssetDirectory();
            foreach (['css', 'js'] as $type) {
                @unlink($dir.'/'.$this->name.'-'.$revision.'.'.$type);
            }
        }
    }

    public function getCSSFiles()
    {
        return $this->getFiles('css', function () {
            return $this->compileCSS();
        });
    }

    public function getJSFiles()
    {
        return $this->getFiles('js', function () {
            return $this->compileJS();
        });
    }

    public function compileLess()
    {
        ini_set('xdebug.max_nesting_level', 200);

        $parser = new Less_Parser(['compress' => true]);

        $css = [];
        $dir = $this->getAssetDirectory();
        foreach ($this->files['less'] as $file) {
            $parser->parseFile($file);
        }

        foreach ($this->less as $less) {
            $parser->parse($less);
        }

        return $parser->getCss();
    }

    public function compileCSS()
    {
        $css = $this->compileLess();

        foreach ($this->files['css'] as $file) {
            $css .= $this->storage->get($file);
        }

        // minify

        return $css;
    }

    public function compileJS()
    {
        $js = '';

        foreach ($this->files['js'] as $file) {
            $js .= $this->storage->get($file);
        }

        // minify

        return $js;
    }
}
