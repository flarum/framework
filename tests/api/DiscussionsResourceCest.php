<?php
use \ApiTester;

class DiscussionsResourceCest {

    protected $endpoint = '/api/discussions';

    public function getDiscussions(ApiTester $I)
    {
        $I->wantTo('get discussions via API');

        $id = $I->haveRecord('discussions', ['title' => 'Game of Thrones', 'last_time' => date('c')]);
        $id2 = $I->haveRecord('discussions', ['title' => 'Lord of the Rings', 'last_time' => date('c')]);

        $I->sendGET($this->endpoint);
        $I->seeResponseCodeIs(200);
        $I->seeResponseIsJson();

        $I->expect('both items are in response');
        $I->seeResponseContainsJson(['id' => (string) $id, 'title' => 'Game of Thrones']);
        $I->seeResponseContainsJson(['id' => (string) $id2, 'title' => 'Lord of the Rings']);

        $I->expect('both items are in root discussions array');
        $I->seeResponseContainsJson(['discussions' => [['id' => (string) $id], ['id' => (string) $id2]]]);
    }

    public function createDiscussion(ApiTester $I)
    {
        $I->wantTo('create a discussion via API');
        $I->haveHttpHeader('Authorization', 'Token 123456');

        $I->sendPOST($this->endpoint, ['discussions' => ['title' => 'foo', 'content' => 'bar']]);
        $I->seeResponseCodeIs(200);
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson(['title' => 'foo']);
        $I->seeResponseContainsJson(['type' => 'comment', 'contentHtml' => '<p>bar</p>']);

        $id = $I->grabDataFromJsonResponse('discussions.id');
        $I->seeRecord('discussions', ['id' => $id, 'title' => 'foo']);
    }
}