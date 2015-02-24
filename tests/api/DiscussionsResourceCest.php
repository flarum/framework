<?php

use Laracasts\TestDummy\Factory;

class DiscussionsResourceCest
{
    protected $endpoint = '/api/discussions';

    public function getDiscussions(ApiTester $I)
    {
        $I->wantTo('get discussions via API');

        $discussions = Factory::times(2)->create('Flarum\Core\Models\Discussion');

        $I->sendGET($this->endpoint);
        $I->seeResponseCodeIs(200);
        $I->seeResponseIsJson();

        $I->expect('there are two discussions in the response');
        $I->assertEquals(2, count($I->grabDataFromJsonResponse('discussions')));

        $I->expect('the discussions exist');
        $I->seeResponseContainsJson(['id' => (string) $discussions[0]->id, 'title' => $discussions[0]->title]);
        $I->seeResponseContainsJson(['id' => (string) $discussions[1]->id, 'title' => $discussions[1]->title]);
    }

    public function showDiscussion(ApiTester $I)
    {
        $I->wantTo('show a single discussion via API');

        $discussion = Factory::create('Flarum\Core\Models\Discussion');

        $I->sendGET($this->endpoint.'/'.$discussion->id);
        $I->seeResponseCodeIs(200);
        $I->seeResponseIsJson();

        $I->expect('the discussion in the response exists');
        $I->seeResponseContainsJson(['discussions' => ['id' => (string) $discussion->id, 'title' => $discussion->title]]);
    }

    public function createDiscussion(ApiTester $I)
    {
        $I->wantTo('create a discussion via API');

        $I->amAuthenticated();

        $I->sendPOST($this->endpoint, ['discussions' => ['title' => 'foo', 'content' => 'bar']]);
        $I->seeResponseCodeIs(200);
        $I->seeResponseIsJson();

        $I->expect('the discussion is included in the response');
        $I->seeResponseContainsJson(['title' => 'foo']);

        $I->expect('posts are included in the response');
        $I->seeResponseContainsJson(['type' => 'comment', 'contentHtml' => '<p>bar</p>']);

        $I->expect('the discussion was created in the database');
        $id = $I->grabDataFromJsonResponse('discussions.id');
        $I->seeRecord('discussions', ['id' => $id, 'title' => 'foo']);
    }

    public function updateDiscussion(ApiTester $I)
    {
        $I->wantTo('update a discussion via API');

        $user = $I->amAuthenticated();

        $discussion = Factory::create('Flarum\Core\Models\Discussion', ['start_user_id' => $user->id]);

        $I->sendPUT($this->endpoint.'/'.$discussion->id, ['discussions' => ['title' => 'foo']]);
        $I->seeResponseCodeIs(200);
        $I->seeResponseIsJson();

        $I->expect('the discussion title was updated');
        $I->seeResponseContainsJson(['title' => 'foo']);

        $I->expect('the discussion was updated in the database');
        $id = $I->grabDataFromJsonResponse('discussions.id');
        $I->seeRecord('discussions', ['id' => $id, 'title' => 'foo']);
    }

    public function deleteDiscussion(ApiTester $I)
    {
        $I->wantTo('delete a discussion via API');

        $user = $I->amAuthenticated();
        $user->groups()->attach(4); // Make the user a moderator

        $discussion = Factory::create('Flarum\Core\Models\Discussion', ['start_user_id' => $user->id]);

        $I->sendDELETE($this->endpoint.'/'.$discussion->id);
        $I->seeResponseCodeIs(204);
        $I->seeResponseEquals('');

        $I->expect('the discussion was deleted in the database');
        $I->dontSeeRecord('discussions', ['id' => $discussion->id]);
    }
}
