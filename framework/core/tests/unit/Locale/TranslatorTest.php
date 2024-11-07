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
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Translation\Loader\ArrayLoader;
use Symfony\Component\Translation\MessageCatalogueInterface;

class TranslatorTest extends TestCase
{
    private const DOMAIN = 'messages'.MessageCatalogueInterface::INTL_DOMAIN_SUFFIX;

    /*
     * These tests should be in sync with JS tests in `js/tests/unit/common/utils/Translator.test.ts`, to make sure that JS
     * translator works in the same way as JS translator.
     */

    #[Test]
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

    #[Test]
    public function missing_placeholders()
    {
        $translator = new Translator('en');
        $translator->addLoader('array', new ArrayLoader());
        $translator->addResource('array', [
            'test1' => 'test1 {placeholder} test1',
        ], 'en', self::DOMAIN);

        $this->assertSame('test1 {placeholder} test1', $translator->trans('test1', []));
    }

    #[Test]
    public function escaped_placeholders()
    {
        $translator = new Translator('en');
        $translator->addLoader('array', new ArrayLoader());
        $translator->addResource('array', [
            'test3' => "test1 {placeholder} '{placeholder}' test1",
        ], 'en', self::DOMAIN);

        $this->assertSame("test1 ' {placeholder} test1", $translator->trans('test3', ['placeholder' => "'"]));
    }

    #[Test]
    public function plural_rules()
    {
        $translator = new Translator('en');
        $translator->addLoader('array', new ArrayLoader());
        $translator->addResource('array', [
            'test4' => '{pageNumber, plural, =1 {{forumName}} other {Page # - {forumName}}}',
        ], 'en', self::DOMAIN);

        $this->assertSame('A & B', $translator->trans('test4', ['forumName' => 'A & B', 'pageNumber' => 1]));
        $this->assertSame('Page 2 - A & B', $translator->trans('test4', ['forumName' => 'A & B', 'pageNumber' => 2]));
    }

    #[Test]
    public function plural_rules_2()
    {
        $translator = new Translator('pl');
        $translator->addLoader('array', new ArrayLoader());
        $translator->addResource('array', [
            'test4' => '{count, plural, one {# post} few {# posty} many {# postów} other {# posta}}',
        ], 'pl', self::DOMAIN);

        $this->assertSame('1 post', $translator->trans('test4', ['count' => 1]));
        $this->assertSame('2 posty', $translator->trans('test4', ['count' => 2]));
        $this->assertSame('5 postów', $translator->trans('test4', ['count' => 5]));
        $this->assertSame('1,5 posta', $translator->trans('test4', ['count' => 1.5]));
    }
}
