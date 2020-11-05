<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\extenders;

use Flarum\Api\Serializer\ForumSerializer;
use Flarum\Extend\ApiSerializer;
use Flarum\Frontend\Document;
use Flarum\Frontend\Frontend;
use Flarum\Tests\integration\TestCase;

class ApiSerializerTest extends TestCase
{
    protected $payload;

    /**
     * @test
     */
    public function attribute_doesnt_exist_by_default()
    {
        $this->app();

        $this->app->getContainer()->resolving(
            'flarum.frontend.forum',
            function (Frontend $frontend) {
                $frontend->content(function (Document $document) {
                    $this->payload = $document->payload;
                });
            }
        );

        $this->send($this->request('GET', '/'));

        $this->assertArrayNotHasKey('customAttribute', $this->payload['resources'][0]['attributes']);
    }

    /**
     * @test
     */
    public function attribute_exists_if_added()
    {
        $this->extend(
            (new ApiSerializer(ForumSerializer::class))
                ->attributes(function () {
                    return [
                        'customAttribute' => true
                    ];
                })->attributes(CustomAttributesInvokableClass::class)
        );

        $this->app();

        $this->app->getContainer()->resolving(
            'flarum.frontend.forum',
            function (Frontend $frontend) {
                $frontend->content(function (Document $document) {
                    $this->payload = $document->payload;
                });
            }
        );

        $this->send($this->request('GET', '/'));

        print_r($this->payload);

        $this->assertArrayHasKey('customAttribute', $this->payload['resources'][0]['attributes']);
        $this->assertArrayHasKey('customAttributeFromInvokable', $this->payload['resources'][0]['attributes']);
    }
}

class CustomAttributesInvokableClass
{
    public function __invoke()
    {
        return [
            'customAttributeFromInvokable' => true
        ];
    }
}
