<?php
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Assets;

use DomainException;

class AssetManager
{
    protected $less;

    protected $js;

    public function __construct(Compiler $js, Compiler $less)
    {
        $this->js = $js;
        $this->less = $less;
    }

    public function addFile($file)
    {
        $ext = pathinfo($file, PATHINFO_EXTENSION);

        switch ($ext) {
            case 'js':
                $this->js->addFile($file);
                break;

            case 'css':
            case 'less':
                $this->less->addFile($file);
                break;

            default:
                throw new DomainException('Unsupported asset type: ' . $ext);
        }
    }

    public function addFiles(array $files)
    {
        array_walk($files, [$this, 'addFile']);
    }

    public function addLess(callable $callback)
    {
        $this->less->addString($callback);
    }

    public function addJs(callable $callback)
    {
        $this->js->addString($callback);
    }

    public function getCssFile()
    {
        return $this->less->getFile();
    }

    public function getJsFile()
    {
        return $this->js->getFile();
    }

    public function flush()
    {
        $this->less->flush();
        $this->js->flush();
    }
}
