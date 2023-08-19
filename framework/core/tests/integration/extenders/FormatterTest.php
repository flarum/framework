<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\extenders;

use Flarum\Extend;
use Flarum\Formatter\Formatter;
use Flarum\Testing\integration\RefreshesFormatterCache;
use Flarum\Testing\integration\TestCase;

class FormatterTest extends TestCase
{
    use RefreshesFormatterCache;

    protected function getFormatter()
    {
        return $this->app()->getContainer()->make(Formatter::class);
    }

    /**
     * @test
     */
    public function custom_formatter_config_doesnt_work_by_default()
    {
        $formatter = $this->getFormatter();

        $this->assertEquals('<t>[B]something[/B]</t>', $formatter->parse('[B]something[/B]'));
    }

    /**
     * @test
     */
    public function custom_formatter_config_works_if_added_with_closure()
    {
        $this->extend((new Extend\Formatter)->configure(function ($config) {
            $config->BBCodes->addFromRepository('B');
        }));

        $formatter = $this->getFormatter();

        $this->assertEquals('<b>something</b>', $formatter->render($formatter->parse('[B]something[/B]')));
    }

    /**
     * @test
     */
    public function custom_formatter_config_works_if_added_with_invokable_class()
    {
        $this->extend((new Extend\Formatter)->configure(InvokableConfig::class));

        $formatter = $this->getFormatter();

        $this->assertEquals('<b>something</b>', $formatter->render($formatter->parse('[B]something[/B]')));
    }

    /**
     * @test
     */
    public function custom_formatter_parsing_doesnt_work_by_default()
    {
        $this->assertEquals('<t>Text&lt;a&gt;</t>', $this->getFormatter()->parse('Text<a>'));
    }

    /**
     * @test
     */
    public function custom_formatter_parsing_works_if_added_with_closure()
    {
        $this->extend((new Extend\Formatter)->parse(function ($parser, $context, $text) {
            return 'ReplacedText<a>';
        }));

        $this->assertEquals('<t>ReplacedText&lt;a&gt;</t>', $this->getFormatter()->parse('Text<a>'));
    }

    /**
     * @test
     */
    public function custom_formatter_parsing_works_if_added_with_invokable_class()
    {
        $this->extend((new Extend\Formatter)->parse(InvokableParsing::class));

        $this->assertEquals('<t>ReplacedText&lt;a&gt;</t>', $this->getFormatter()->parse('Text<a>'));
    }

    /**
     * @test
     */
    public function custom_formatter_unparsing_doesnt_work_by_default()
    {
        $this->assertEquals('Text<a>', $this->getFormatter()->unparse('<t>Text&lt;a&gt;</t>'));
    }

    /**
     * @test
     */
    public function custom_formatter_unparsing_works_if_added_with_closure()
    {
        $this->extend((new Extend\Formatter)->unparse(function ($context, $xml) {
            return '<t>ReplacedText&lt;a&gt;</t>';
        }));

        $this->assertEquals('ReplacedText<a>', $this->getFormatter()->unparse('<t>Text&lt;a&gt;</t>'));
    }

    /**
     * @test
     */
    public function custom_formatter_unparsing_works_if_added_with_invokable_class()
    {
        $this->extend((new Extend\Formatter)->unparse(InvokableUnparsing::class));

        $this->assertEquals('ReplacedText<a>', $this->getFormatter()->unparse('<t>Text&lt;a&gt;</t>'));
    }

    /**
     * @test
     */
    public function custom_formatter_rendering_doesnt_work_by_default()
    {
        $this->assertEquals('Text', $this->getFormatter()->render('<p>Text</p>'));
    }

    /**
     * @test
     */
    public function custom_formatter_rendering_works_if_added_with_closure()
    {
        $this->extend((new Extend\Formatter)->render(function ($renderer, $context, $xml, $request) {
            return '<html>ReplacedText</html>';
        }));

        $this->assertEquals('ReplacedText', $this->getFormatter()->render('<html>Text</html>'));
    }

    /**
     * @test
     */
    public function custom_formatter_rendering_works_if_added_with_invokable_class()
    {
        $this->extend((new Extend\Formatter)->render(InvokableRendering::class));

        $this->assertEquals('ReplacedText', $this->getFormatter()->render('<html>Text</html>'));
    }
}

class InvokableConfig
{
    public function __invoke($config)
    {
        $config->BBCodes->addFromRepository('B');
    }
}

class InvokableParsing
{
    public function __invoke($parser, $context, $text)
    {
        return 'ReplacedText<a>';
    }
}

class InvokableUnparsing
{
    public function __invoke($context, $xml)
    {
        return '<t>ReplacedText&lt;a&gt;</t>';
    }
}

class InvokableRendering
{
    public function __invoke($renderer, $context, $xml, $request)
    {
        return '<html>ReplacedText</html>';
    }
}
