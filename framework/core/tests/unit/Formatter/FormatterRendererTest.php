<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\unit\Formatter;

use FilesystemIterator;
use Flarum\Formatter\Formatter;
use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\Repository;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use s9e\TextFormatter\Configurator;
use s9e\TextFormatter\Renderer;

/**
 * Rendering a list renders every post in it, and the cached formatter holds the
 * parser and the JavaScript as well as the renderer: each render fetched and
 * unserialized all of it again, for a renderer it already had.
 */
class FormatterRendererTest extends TestCase
{
    private string $cacheDir;

    private Repository $cache;

    private CountingArrayStore $store;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cacheDir = sys_get_temp_dir().'/flarum-formatter-'.bin2hex(random_bytes(8));
        mkdir($this->cacheDir);

        // A store that hands back a fresh copy on every read, as the file and
        // Redis stores do.
        $this->store = new CountingArrayStore(serializesValues: true);
        $this->cache = new Repository($this->store);

        $configurator = new Configurator;
        $configurator->rendering->setEngine('PHP');
        $configurator->rendering->getEngine()->cacheDir = $this->cacheDir; // @phpstan-ignore-line
        $configurator->rendering->parameters['GREETING'] = 'pristine';
        $configurator->tags->add('GREET')->template = '<b><xsl:value-of select="$GREETING"/></b>';

        $this->cache->forever('flarum.formatter', $configurator->finalize());
        $this->store->reads = 0;
    }

    protected function tearDown(): void
    {
        foreach (new FilesystemIterator($this->cacheDir) as $file) {
            unlink($file->getPathname());
        }

        rmdir($this->cacheDir);

        parent::tearDown();
    }

    #[Test]
    public function rendering_reads_the_formatter_from_the_cache_once(): void
    {
        $formatter = new Formatter($this->cache, $this->cacheDir);

        foreach (range(1, 5) as $post) {
            $this->assertSame('<b>pristine</b>', $formatter->render('<r><GREET/></r>'));
        }

        $this->assertSame(1, $this->store->reads, 'Each render read the whole formatter from the cache again.');
    }

    #[Test]
    public function rendering_registers_the_renderer_autoloader_once(): void
    {
        $formatter = new Formatter($this->cache, $this->cacheDir);
        $before = count(spl_autoload_functions());

        foreach (range(1, 5) as $post) {
            $formatter->render('<r><GREET/></r>');
        }

        $this->assertSame($before + 1, count(spl_autoload_functions()), 'Each render registered another autoloader.');
    }

    #[Test]
    public function each_render_starts_from_the_renderer_as_compiled(): void
    {
        $formatter = new Formatter($this->cache, $this->cacheDir);

        // A rendering callback may set a parameter for the post it renders.
        $first = true;
        $formatter->addRenderingCallback(function (Renderer $renderer, mixed $context, string $xml) use (&$first): string {
            if ($first) {
                $renderer->setParameter('GREETING', 'just this post');
                $first = false;
            }

            return $xml;
        });

        $this->assertSame('<b>just this post</b>', $formatter->render('<r><GREET/></r>'));
        $this->assertSame('<b>pristine</b>', $formatter->render('<r><GREET/></r>'), 'A parameter set for one post carried over to the next.');
    }

    #[Test]
    public function flushing_makes_the_next_render_read_the_formatter_again(): void
    {
        $formatter = new Formatter($this->cache, $this->cacheDir);
        $formatter->render('<r><GREET/></r>');

        $bundle = $this->cache->get('flarum.formatter');
        $formatter->flush();
        $this->cache->forever('flarum.formatter', $bundle);
        $this->store->reads = 0;

        $formatter->render('<r><GREET/></r>');

        $this->assertSame(1, $this->store->reads, 'A flushed formatter kept rendering with what it had before.');
    }
}

class CountingArrayStore extends ArrayStore
{
    public int $reads = 0;

    public function get($key)
    {
        if ($key === 'flarum.formatter') {
            $this->reads++;
        }

        return parent::get($key);
    }
}
