<?php namespace Flarum\Core\Formatter;

use Illuminate\Contracts\Cache\Repository;
use s9e\TextFormatter\Configurator;
use s9e\TextFormatter\Unparser;

class Formatter
{
    protected $cache;

    public function __construct(Repository $cache)
    {
        $this->cache = $cache;
    }

    protected function getConfigurator()
    {
        $configurator = new Configurator;
        $configurator->rootRules->enableAutoLineBreaks();

        $configurator->BBCodes->addFromRepository('B');
        $configurator->BBCodes->addFromRepository('I');
        $configurator->BBCodes->addFromRepository('U');
        $configurator->BBCodes->addFromRepository('S');
        $configurator->BBCodes->addFromRepository('COLOR');
        $configurator->BBCodes->addFromRepository('URL');
        $configurator->BBCodes->addFromRepository('EMAIL');
        $configurator->BBCodes->addFromRepository('CODE');
        $configurator->BBCodes->addFromRepository('QUOTE');
        $configurator->BBCodes->addFromRepository('LIST');
        $configurator->BBCodes->addFromRepository('*');
        $configurator->BBCodes->addFromRepository('SPOILER');

        $configurator->Autoemail;
        $configurator->Autolink;

        $configurator->Litedown;

        $configurator->Emoticons->add(':)', '😀');

        return $configurator;
    }

    protected function getComponent($key)
    {
        $cacheKey = 'flarum.formatter.' . $key;

        return $this->cache->rememberForever($cacheKey, function () use ($key) {
            return $this->getConfigurator()->finalize()[$key];
        });
    }

    protected function getParser()
    {
        return $this->getComponent('parser');
    }

    protected function getRenderer()
    {
        return $this->getComponent('renderer');
    }

    public function getJS()
    {
        $configurator = $this->getConfigurator();
        $configurator->enableJavaScript();
        $configurator->javascript->setMinifier('ClosureCompilerService');

        return $configurator->finalize([
            'returnParser'   => false,
            'returnRenderer' => false
        ])['js'];
    }

    public function parse($text)
    {
        $parser = $this->getParser();

        return $parser->parse($text);
    }

    public function render($xml)
    {
        $renderer = $this->getRenderer();

        return $renderer->render($xml);
    }

    public function unparse($xml)
    {
        return Unparser::unparse($xml);
    }
}
