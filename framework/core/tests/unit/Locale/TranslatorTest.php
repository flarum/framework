<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\unit\Locale;

use Flarum\Locale\Translator;
use Flarum\Testing\unit\TestCase;
use Symfony\Component\Translation\Loader\ArrayLoader;
use Symfony\Component\Translation\MessageCatalogueInterface;

class TranslatorTest extends TestCase
{
    private const DOMAIN = 'messages'.MessageCatalogueInterface::INTL_DOMAIN_SUFFIX;

    /*
     * These tests should be in sync with JS tests in `js/tests/unit/common/utils/Translator.test.ts`, to make sure that JS
     * translator works in the same way as JS translator.
     */

    /** @test */
    public function placeholders_encoding()
    {
        $translator = new Translator('en');
        $translator->addLoader('array', new ArrayLoader());
        $translator->addResource('array', [
            'test1' => 'test1 {placeholder} test1',
            'test2' => 'test2 {placeholder} test2',
        ], 'en', self::DOMAIN);

        $this->assertSame("test1 ' test1", $translator->trans('test1', ['placeholder' => "'"]));
        $this->assertSame("test1 test2 ' test2 test1", $translator->trans('test1', ['placeholder' => $translator->trans('test2', ['placeholder' => "'"])]));
    }

    /** @test */
    public function missing_placeholders()
    {
        $translator = new Translator('en');
        $translator->addLoader('array', new ArrayLoader());
        $translator->addResource('array', [
            'test1' => 'test1 {placeholder} test1',
        ], 'en', self::DOMAIN);

        $this->assertSame('test1 {placeholder} test1', $translator->trans('test1', []));
    }

    /** @test */
    public function escaped_placeholders()
    {
        $translator = new Translator('en');
        $translator->addLoader('array', new ArrayLoader());
        $translator->addResource('array', [
            'test3' => "test1 {placeholder} '{placeholder}' test1",
        ], 'en', self::DOMAIN);

        $this->assertSame("test1 ' {placeholder} test1", $translator->trans('test3', ['placeholder' => "'"]));
    }
}
