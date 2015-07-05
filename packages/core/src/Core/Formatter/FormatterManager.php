<?php namespace Flarum\Core\Formatter;

use Flarum\Core\Model;
use Illuminate\Contracts\Container\Container;
use HTMLPurifier;
use HTMLPurifier_Config;
use LogicException;

class FormatterManager
{
    /**
     * @var Container
     */
    protected $container;

    /**
     * @var array
     */
    protected $formatters = [];

    /**
     * @var HTMLPurifier_Config
     */
    protected $htmlPurifierConfig;

    /**
     * Create a new formatter manager instance.
     *
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;

        // TODO: Studio does not yet merge autoload_files...
        // https://github.com/franzliedke/studio/commit/4f0f4314db4ed3e36c869a5f79b855c97bdd1be7
        require __DIR__.'/../../../vendor/ezyang/htmlpurifier/library/HTMLPurifier.composer.php';

        $this->htmlPurifierConfig = $this->getDefaultHtmlPurifierConfig();
    }

    /**
     * Get the HTMLPurifier configuration object.
     *
     * @return HTMLPurifier_Config
     */
    public function getHtmlPurifierConfig()
    {
        return $this->htmlPurifierConfig;
    }

    /**
     * Add a new formatter.
     *
     * @param string $formatter
     */
    public function add($formatter)
    {
        $this->formatters[] = $formatter;
    }

    /**
     * Format the given text using the collected formatters.
     *
     * @param string $text
     * @param Model|null $model The entity that owns the text.
     * @return string
     */
    public function format($text, Model $model = null)
    {
        $formatters = $this->getFormatters();

        foreach ($formatters as $formatter) {
            $formatter->config($this);
        }

        foreach ($formatters as $formatter) {
            $text = $formatter->formatBeforePurification($text, $model);
        }

        $text = $this->purify($text);

        foreach ($formatters as $formatter) {
            $text = $formatter->formatAfterPurification($text, $model);
        }

        return $text;
    }

    /**
     * Instantiate the collected formatters.
     *
     * @return Formatter[]
     */
    protected function getFormatters()
    {
        $formatters = [];

        foreach ($this->formatters as $formatter) {
            $formatter = $this->container->make($formatter);

            if (! $formatter instanceof Formatter) {
                throw new LogicException('Formatter ' . get_class($formatter)
                    . ' does not implement ' . Formatter::class);
            }

            $formatters[] = $formatter;
        }

        return $formatters;
    }

    /**
     * Purify the given text, making sure it is safe to be displayed in web
     * browsers.
     *
     * @param string $text
     * @return string
     */
    protected function purify($text)
    {
        $purifier = new HTMLPurifier($this->htmlPurifierConfig);

        return $purifier->purify($text);
    }

    /**
     * Get the default HTMLPurifier config settings.
     *
     * @return HTMLPurifier_Config
     */
    protected function getDefaultHtmlPurifierConfig()
    {
        $config = HTMLPurifier_Config::createDefault();
        $config->set('Core.Encoding', 'UTF-8');
        $config->set('Core.EscapeInvalidTags', true);
        $config->set('HTML.Doctype', 'HTML 4.01 Strict');
        $config->set('HTML.Allowed', 'p,em,strong,a[href|title],ul,ol,li,code,pre,blockquote,h1,h2,h3,h4,h5,h6,br,hr,img[src|alt]');
        $config->set('HTML.Nofollow', true);

        return $config;
    }
}
