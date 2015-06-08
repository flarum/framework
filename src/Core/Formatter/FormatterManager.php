<?php namespace Flarum\Core\Formatter;

use Illuminate\Container\Container;
use HTMLPurifier;
use HTMLPurifier_Config;

class FormatterManager
{
    protected $formatters = [];

    /**
     * The IoC container instance.
     *
     * @var \Illuminate\Container\Container
     */
    protected $container;

    public $config;

    /**
     * Create a new formatter manager instance.
     *
     * @param  \Illuminate\Container\Container  $container
     * @return void
     */
    public function __construct(Container $container = null)
    {
        $this->container = $container ?: new Container;

        // Studio does not yet merge autoload_files...
        // https://github.com/franzliedke/studio/commit/4f0f4314db4ed3e36c869a5f79b855c97bdd1be7
        require __DIR__.'/../../../vendor/ezyang/htmlpurifier/library/HTMLPurifier.composer.php';

        $this->config = HTMLPurifier_Config::createDefault();
        $this->config->set('Core.Encoding', 'UTF-8');
        $this->config->set('Core.EscapeInvalidTags', true);
        $this->config->set('HTML.Doctype', 'HTML 4.01 Strict');
        $this->config->set('HTML.Allowed', 'p,em,strong,a[href|title],ul,ol,li,code,pre,blockquote,h1,h2,h3,h4,h5,h6,br,hr,img[src|alt]');
        $this->config->set('HTML.Nofollow', true);
    }

    public function add($name, $formatter, $priority = 0)
    {
        $this->formatters[$name] = [$formatter, $priority];
    }

    public function remove($name)
    {
        unset($this->formatters[$name]);
    }

    protected function getFormatters()
    {
        $sorted = [];

        foreach ($this->formatters as $array) {
            list($formatter, $priority) = $array;
            $sorted[$priority][] = $formatter;
        }

        ksort($sorted);

        $result = [];

        foreach ($sorted as $formatters) {
            $result = array_merge($result, $formatters);
        }

        return $result;
    }

    public function format($text, $post = null)
    {
        $formatters = [];
        foreach ($this->getFormatters() as $formatter) {
            $formatters[] = $this->container->make($formatter);
        }

        foreach ($formatters as $formatter) {
            $text = $formatter->beforePurification($text, $post);
        }

        $purifier = new HTMLPurifier($this->config);

        $text = $purifier->purify($text);

        foreach ($formatters as $formatter) {
            $text = $formatter->afterPurification($text, $post);
        }

        return $text;
    }
}
