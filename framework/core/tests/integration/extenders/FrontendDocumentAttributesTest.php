<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\extenders;

use Flarum\Extend\Frontend;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ServerRequestInterface;

class FrontendDocumentAttributesTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    private function html(): string
    {
        return $this->send($this->request('GET', '/'))->getBody()->getContents();
    }

    /**
     * The opening `<html>` tag, which is where these end up.
     */
    private function htmlTag(): string
    {
        preg_match('/<html[^>]*>/', $this->html(), $matches);

        return $matches[0] ?? '';
    }

    #[Test]
    public function extra_document_classes_reach_the_html_tag()
    {
        $this->extend(
            (new Frontend('forum'))->extraDocumentClasses('some-class')
        );

        $this->assertStringContainsString('some-class', $this->htmlTag());
    }

    #[Test]
    public function extra_document_classes_accept_an_array()
    {
        $this->extend(
            (new Frontend('forum'))->extraDocumentClasses(['first-class', 'second-class' => true, 'absent-class' => false])
        );

        $tag = $this->htmlTag();

        $this->assertStringContainsString('first-class', $tag);
        $this->assertStringContainsString('second-class', $tag);
        $this->assertStringNotContainsString('absent-class', $tag);
    }

    #[Test]
    public function extra_document_classes_accept_a_callable()
    {
        $this->extend(
            (new Frontend('forum'))->extraDocumentClasses(function (ServerRequestInterface $request) {
                return 'from-callable';
            })
        );

        $this->assertStringContainsString('from-callable', $this->htmlTag());
    }

    #[Test]
    public function classes_from_several_extenders_all_arrive()
    {
        $this->extend(
            (new Frontend('forum'))->extraDocumentClasses('first-extender'),
            (new Frontend('forum'))->extraDocumentClasses('second-extender')
        );

        $tag = $this->htmlTag();

        $this->assertStringContainsString('first-extender', $tag);
        $this->assertStringContainsString('second-extender', $tag);
    }

    #[Test]
    public function extra_document_attributes_reach_the_html_tag()
    {
        $this->extend(
            (new Frontend('forum'))->extraDocumentAttributes(['data-test' => 'value'])
        );

        $this->assertStringContainsString('data-test="value"', $this->htmlTag());
    }

    #[Test]
    public function extra_document_attributes_accept_a_callable()
    {
        $this->extend(
            (new Frontend('forum'))->extraDocumentAttributes([
                'data-test' => function (ServerRequestInterface $request) {
                    return 'from-callable';
                },
            ])
        );

        $this->assertStringContainsString('data-test="from-callable"', $this->htmlTag());
    }

    #[Test]
    public function an_attribute_value_that_shares_a_name_with_a_global_function_is_not_called()
    {
        // `is_callable('value')` is true, because Laravel defines a global
        // `value()` helper. Treating a string value as callable therefore
        // called it with the request and tried to escape a ServerRequest.
        $this->extend(
            (new Frontend('forum'))->extraDocumentAttributes(['data-test' => 'value'])
        );

        $this->assertStringContainsString('data-test="value"', $this->htmlTag());
    }

    #[Test]
    public function a_class_that_shares_a_name_with_a_global_function_is_not_called()
    {
        $this->extend(
            (new Frontend('forum'))->extraDocumentClasses('value')
        );

        $this->assertStringContainsString('value', $this->htmlTag());
    }

    #[Test]
    public function no_numerically_keyed_attribute_is_emitted()
    {
        // The shape of the bug this covers: attributes were appended to the
        // document's map at a numeric index, so the class never reached
        // `makeExtraClasses` and an attribute named `0` was written instead.
        $this->extend(
            (new Frontend('forum'))->extraDocumentClasses('some-class')
        );

        $this->assertDoesNotMatchRegularExpression('/<html[^>]*\s\d+=/', $this->htmlTag());
    }
}
