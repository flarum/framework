<?php

use Laracasts\TestDummy\Factory;
use \Mockery as m;
use Flarum\Core\Users\User;
use Flarum\Core\Discussions\Discussion;
use Flarum\Core\Discussions\DiscussionRepository;
use Codeception\Util\Stub;

class DiscussionRepositoryTest extends \Codeception\TestCase\Test
{
    /**
     * @var \IntegrationTester
     */
    protected $tester;

    protected function _before()
    {  
        $this->repo = new DiscussionRepository;
    }

    /** @test */
    public function it_gets_a_discussion_by_id()
    {
        // Given I have a discussion
        $discussion = Factory::create('Flarum\Core\Discussions\Discussion');

        // When I fetch the discussion by its ID
        $result = $this->repo->find($discussion->id);

        // Then I should receive it
        $this->assertEquals($discussion->id, $result->id);
    }

    /**
     * @test
     * @expectedException Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function it_throws_an_exception_when_a_discussion_cannot_be_viewed()
    {
        // Given I have a discussion
        $discussion = Factory::create('Flarum\Core\Discussions\Discussion');

        // And forum permissions do not allow guests to view the forum
        $manager = m::mock('Flarum\Core\Permissions\Manager');
        $manager->shouldReceive('granted')->andReturn(false);
        app()->instance('flarum.permissions', $manager);

        // When I fetch the discussion by its ID, I expect an exception to be thrown
        $this->repo->findOrFail($discussion->id, new Flarum\Core\Users\Guest);
    }

    /** @test */
    public function it_saves_a_discussion()
    {
        // Given I have a new discussion
        $user = Factory::create('Flarum\Core\Users\User');
        $discussion = Discussion::start('foo', $user);

        // When I save it
        $discussion = $this->repo->save($discussion);

        // Then it should be in the database
        $this->tester->seeRecord('discussions', ['title' => 'foo']);
    }

    /**
     * @test
     * @expectedException Flarum\Core\Support\Exceptions\ValidationFailureException
     */
    public function it_will_not_save_an_invalid_discussion()
    {
        // Given I have a new discussion containing no information
        $discussion = new Discussion;

        // When I save it, I expect an exception to be thrown
        $this->repo->save($discussion);
    }

    /** @test */
    public function it_deletes_a_discussion()
    {
        // Given I have a discussion
        $discussion = Factory::create('Flarum\Core\Discussions\Discussion');

        // When I delete it
        $this->repo->delete($discussion);

        // Then it should no longer be in the database
        $this->tester->dontSeeRecord('discussions', ['id' => $discussion->id]);
    }

}