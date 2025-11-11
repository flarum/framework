<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tags\Tests\integration\api\forum;

use Flarum\Tags\Tests\integration\RetrievesRepresentativeTags;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Illuminate\Support\Arr;

class ForumAttributeTest extends TestCase
{
    use RetrievesAuthorizedUsers;
    use RetrievesRepresentativeTags;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-tags');
        $this->extension('flarum-suspend');

        $this->prepareDatabase([
            'tags' => $this->tags(),
            'users' => [
                $this->normalUser(),
                ['id' => 3, 'username' => 'suspended-user', 'email' => 'suspended-user@machine.local', 'suspended_until' => '2043-11-17 22:05:23', 'is_email_confirmed' => 1],
            ]
        ]);
    }

    public function canStartDiscussionProvider()
    {
        return [
            'admin user, min 0/0' => ['authenticatedAs' => 1, 'minPrimary' => 0, 'minSecondary' => 0, 'expected' => true],
            'normal user, min 0/0' => ['authenticatedAs' => 2, 'minPrimary' => 0, 'minSecondary' => 0, 'expected' => true],
            'suspended user, min 0/0' => ['authenticatedAs' => 3, 'minPrimary' => 0, 'minSecondary' => 0, 'expected' => false],
            'guest user, min 0/0' => ['authenticatedAs' => null, 'minPrimary' => 0, 'minSecondary' => 0, 'expected' => false],

            'admin user, min 1/0' => ['authenticatedAs' => 1, 'minPrimary' => 1, 'minSecondary' => 0, 'expected' => true],
            'normal user, min 1/0' => ['authenticatedAs' => 2, 'minPrimary' => 1, 'minSecondary' => 0, 'expected' => true],
            'suspended user, min 1/0' => ['authenticatedAs' => 3, 'minPrimary' => 1, 'minSecondary' => 0, 'expected' => false],
            'guest user, min 1/0' => ['authenticatedAs' => null, 'minPrimary' => 1, 'minSecondary' => 0, 'expected' => false],

            'admin user, min 0/1' => ['authenticatedAs' => 1, 'minPrimary' => 0, 'minSecondary' => 1, 'expected' => true],
            'normal user, min 0/1' => ['authenticatedAs' => 2, 'minPrimary' => 0, 'minSecondary' => 1, 'expected' => true],
            'suspended user, min 0/1' => ['authenticatedAs' => 3, 'minPrimary' => 0, 'minSecondary' => 1, 'expected' => false],
            'guest user, min 0/1' => ['authenticatedAs' => null, 'minPrimary' => 0, 'minSecondary' => 1, 'expected' => false],

            'admin user, min 1/1' => ['authenticatedAs' => 1, 'minPrimary' => 1, 'minSecondary' => 1, 'expected' => true],
            'normal user, min 1/1' => ['authenticatedAs' => 2, 'minPrimary' => 1, 'minSecondary' => 1, 'expected' => true],
            'suspended user, min 1/1' => ['authenticatedAs' => 3, 'minPrimary' => 1, 'minSecondary' => 1, 'expected' => false],
            'guest user, min 1/1' => ['authenticatedAs' => null, 'minPrimary' => 1, 'minSecondary' => 1, 'expected' => false],
        ];
    }

    /**
     * @test
     *
     * @dataProvider canStartDiscussionProvider
     */
    public function it_returns_the_expected_can_start_discussion_attribute(
        ?int $authenticatedAs,
        int $minPrimary,
        int $minSecondary,
        bool $expected
    ) {
        $this->setting('flarum-tags.min_primary_tags', $minPrimary);
        $this->setting('flarum-tags.min_secondary_tags', $minSecondary);

        $response = $this->send(
            $this->request('GET', '/api', [
                'authenticatedAs' => $authenticatedAs,
            ])
        );

        $this->assertEquals(200, $response->getStatusCode());

        $json = json_decode($response->getBody()->getContents(), true);
        $this->assertEquals($expected, Arr::get($json, 'data.attributes.canStartDiscussion'));
    }
}
