<?php namespace Flarum\Core\Formatter;

use Illuminate\Contracts\Container\Container;
use HTMLPurifier;
use HTMLPurifier_Config;

class FormatterManager
{
    protected $formatters = [];

    /**
     * The IoC container instance.
     *
     * @var \Illuminate\Contracts\Container\Container
     */
    protected $container;

    /**
     * Create a new formatter manager instance.
     *
     * @param  \Illuminate\Contracts\Container\Container  $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
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

        // Studio does not yet merge autoload_files...
        // https://github.com/franzliedke/studio/commit/4f0f4314db4ed3e36c869a5f79b855c97bdd1be7
        require __DIR__.'/../../../vendor/ezyang/htmlpurifier/library/HTMLPurifier.composer.php';

        $config = HTMLPurifier_Config::createDefault();
        $config->set('Core.Encoding', 'UTF-8');
        $config->set('Core.EscapeInvalidTags', true);
        $config->set('HTML.Doctype', 'HTML 4.01 Strict');
        $config->set('HTML.Allowed', 'p,em,strong,a[href|title],ul,ol,li,code,pre,blockquote,h1,h2,h3,h4,h5,h6,br,hr');
        $config->set('HTML.Nofollow', true);

        $purifier = new HTMLPurifier($config);

        $text = $purifier->purify($text);

        foreach ($formatters as $formatter) {
            $text = $formatter->afterPurification($text, $post);
        }

        return $text;
    }
}
