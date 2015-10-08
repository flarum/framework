<?php
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Asset;

use DomainException;
use Flarum\Asset\CompilerInterface;

class AssetManager
{
    /**
     * @var CompilerInterface
     */
    protected $less;

    /**
     * @var CompilerInterface
     */
    protected $js;

    /**
     * @param CompilerInterface $js
     * @param CompilerInterface $less
     */
    public function __construct(CompilerInterface $js, CompilerInterface $less)
    {
        $this->js = $js;
        $this->less = $less;
    }

    /**
     * @param $filename
     */
    public function setFilename($filename)
    {
        $this->js->setFilename($filename . '.js');
        $this->less->setFilename($filename . '.css');
    }

    /**
     * @param string $file
     */
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

    /**
     * @param string[] $files
     */
    public function addFiles(array $files)
    {
        array_walk($files, [$this, 'addFile']);
    }

    /**
     * @param callable $callback
     */
    public function addLess(callable $callback)
    {
        $this->less->addString($callback);
    }

    /**
     * @param callable $callback
     */
    public function addJs(callable $callback)
    {
        $this->js->addString($callback);
    }

    /**
     * @return string
     */
    public function getCssFile()
    {
        return $this->less->getFile();
    }

    /**
     * @return string
     */
    public function getJsFile()
    {
        return $this->js->getFile();
    }

    public function flushCss()
    {
        $this->less->flush();
    }

    public function flushJs()
    {
        $this->js->flush();
    }
}
