<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\api\discussions;

use Carbon\Carbon;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Illuminate\Support\Arr;

class ListWithFulltextSearchTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->database()->rollBack();

        $this->populateSimpleTestData();

        $this->populateRealDataForScoreTests();

        // We need to call these again, since we rolled back the transaction started by `::app()`.
        $this->database()->beginTransaction();

        $this->populateDatabase();
    }

    private function populateSimpleTestData(): void
    {
        // We need to insert these outside of a transaction, because FULLTEXT indexing,
        // which is needed for search, doesn't happen in transactions.
        // We clean it up explicitly at the end.
        $this->database()->table('discussions')->insert([
            ['id' => 1, 'title' => 'lightsail in title', 'created_at' => Carbon::now()->toDateTimeString(), 'user_id' => 1, 'comment_count' => 1],
            ['id' => 2, 'title' => 'lightsail in title too', 'created_at' => Carbon::createFromDate(2020, 01, 01)->toDateTimeString(), 'user_id' => 1, 'comment_count' => 1],
            ['id' => 3, 'title' => 'not in title either', 'created_at' => Carbon::now()->toDateTimeString(), 'user_id' => 1, 'comment_count' => 1],
            ['id' => 4, 'title' => 'not in title or text', 'created_at' => Carbon::now()->toDateTimeString(), 'user_id' => 1, 'comment_count' => 1],
            ['id' => 5, 'title' => 'తెలుగు', 'created_at' => Carbon::now()->toDateTimeString(), 'user_id' => 1, 'comment_count' => 1],
            ['id' => 6, 'title' => '支持中文吗', 'created_at' => Carbon::now()->toDateTimeString(), 'user_id' => 1, 'comment_count' => 1],
        ]);

        $this->database()->table('posts')->insert([
            ['id' => 1, 'discussion_id' => 1, 'created_at' => Carbon::now()->toDateTimeString(), 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p>not in text</p></t>'],
            ['id' => 2, 'discussion_id' => 2, 'created_at' => Carbon::now()->toDateTimeString(), 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p>lightsail in text</p></t>'],
            ['id' => 3, 'discussion_id' => 2, 'created_at' => Carbon::now()->toDateTimeString(), 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p>another lightsail for discussion 2!</p></t>'],
            ['id' => 4, 'discussion_id' => 3, 'created_at' => Carbon::now()->toDateTimeString(), 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p>just one lightsail for discussion 3.</p></t>'],
            ['id' => 5, 'discussion_id' => 4, 'created_at' => Carbon::now()->toDateTimeString(), 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p>not in title or text</p></t>'],
            ['id' => 6, 'discussion_id' => 4, 'created_at' => Carbon::now()->toDateTimeString(), 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p>తెలుగు</p></t>'],
            ['id' => 7, 'discussion_id' => 2, 'created_at' => Carbon::now()->toDateTimeString(), 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p>支持中文吗</p></t>'],
        ]);
    }

    private function populateRealDataForScoreTests(): void
    {
        // Disable forign key constraints temporarily
        $this->database()->statement('SET FOREIGN_KEY_CHECKS=0');

        $this->database()->table('discussions')->insert([
            ['id' => 7, 'title' => 'User should be able to promote the visible, foreground, non-saved window to a saved window', 'created_at' => Carbon::now()->toDateTimeString(), 'user_id' => 1, 'comment_count' => 1, 'first_post_id' => 8],
            ['id' => 8, 'title' => 'Moving Tab in Saved Window to New Window Destroys Non-Saved Window', 'created_at' => Carbon::now()->toDateTimeString(), 'user_id' => 1, 'comment_count' => 1, 'first_post_id' => 16],
            ['id' => 9, 'title' => 'Current saved window is renamed when moving normal window to saved window area', 'created_at' => Carbon::now()->toDateTimeString(), 'user_id' => 1, 'comment_count' => 1, 'first_post_id' => 18],
            ['id' => 10, 'title' => 'Save bookmarks edit page window position is not saved', 'created_at' => Carbon::now()->toDateTimeString(), 'user_id' => 1, 'comment_count' => 1, 'first_post_id' => 19],
            ['id' => 11, 'title' => 'Favicons in the saved bookmarks flash on and off', 'created_at' => Carbon::now()->toDateTimeString(), 'user_id' => 1, 'comment_count' => 1, 'first_post_id' => 20],
            ['id' => 12, 'title' => 'User should be able to switch \'in-place\' between non-saved windows', 'created_at' => Carbon::now()->toDateTimeString(), 'user_id' => 1, 'comment_count' => 1, 'first_post_id' => 23],
        ]);

        $this->database()->table('posts')->insert([
            ['id' => 8, 'discussion_id' => 7, 'created_at' => Carbon::now()->toDateTimeString(), 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p>Unless I\'m missing something, currently there does not appear to be a way to promote a non-saved window to a saved window. A user must be click the \'+\' button in the window menu dropdown, then re-open all open tabs in the current window in the new saved window. That doesn\'t sound fun if one has multiple tabs open.</p>

<p>This would be ameliorated if it was possible move tabs to saved windows without physical drag and drop but that doesn\'t seem possible as well, see User should be able to move tabs from visible window to non-visible saved windows</p></t>'],
            ['id' => 9, 'discussion_id' => 7, 'created_at' => Carbon::now()->toDateTimeString(), 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p>open sidebar<br/>
switch to either Tabs or Windows view in sidebar<br/>
right click on window title in side bar (eg. "3 Tabs")<br/>
click "Save..."<br/>
OR<br/>
click once on window title in sidebar (eg. "3 Tabs")<br/>
rename window in-place<br/>
I think this is how it is supposed to work.</p>

<p>For me, at least, this funcionality doesn\'t work due to (I imagine) a bug...<br/>
Unless I\'ve misunderstood you?</p></t>'],
            ['id' => 10, 'discussion_id' => 7, 'created_at' => Carbon::now()->toDateTimeString(), 'user_id' => 1, 'type' => 'comment', 'content' => '<r><p>Are you refering to this?</p>

<p><URL url="https://orionfeedback.org/d/4258-window-switcher-i-am-able-to-drag-the-unsaved-window-into-saved-windows-and-it-breaks-the-ui-until-restart">https://orionfeedback.org/d/4258-window-switcher-i-am-able-to-drag-the-unsaved-window-into-saved-windows-and-it-breaks-the-ui-until-restart</URL></p></r>'],
            ['id' => 11, 'discussion_id' => 7, 'created_at' => Carbon::now()->toDateTimeString(), 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p>robrecord Yes! Wasn\'t apparent that was the trick. Thanks for pointing it out.<br/>
Vlad My other logged issue seems related to that one</p></t>'],
            ['id' => 12, 'discussion_id' => 7, 'created_at' => Carbon::now()->toDateTimeString(), 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p>b3noit Which one?</p>

<p>Is there anything still being asked here?</p></t>'],
            ['id' => 13, 'discussion_id' => 7, 'created_at' => Carbon::now()->toDateTimeString(), 'user_id' => 1, 'type' => 'comment', 'content' => '<r><p>Vlad This one <URL url="https://orionfeedback.org/d/4288-moving-tab-in-saved-window-to-new-window-destroys-non-saved-window">https://orionfeedback.org/d/4288-moving-tab-in-saved-window-to-new-window-destroys-non-saved-window</URL></p>

<p>The post above yours clarified how the UI is supposed to be used here</p></r>'],
            ['id' => 14, 'discussion_id' => 7, 'created_at' => Carbon::now()->toDateTimeString(), 'user_id' => 1, 'type' => 'comment', 'content' => '<t>
<p>b3noit So is there an ask here or I can close this?</p></t>'],
            ['id' => 15, 'discussion_id' => 7, 'created_at' => Carbon::now()->toDateTimeString(), 'user_id' => 1, 'type' => 'comment', 'content' => '<t>
<p>b3noit ping</p></t>'],

            ['id' => 26, 'discussion_id' => 12, 'created_at' => Carbon::now()->toDateTimeString(), 'user_id' => 1, 'type' => 'comment', 'content' => "<t><p>Visible, non-saved windows cannot participate in 'in-place' window switching, they can only be switched via the OS window manager. Its unclear why only saved windows can participate, at the least non-saved windows should be able to opt-in so one window can switch between both saved and non-saved windows.</p></t>"],
            ['id' => 25, 'discussion_id' => 12, 'created_at' => Carbon::now()->toDateTimeString(), 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p>@dino can you explain why we decided this?</p>

</t>'],
            ['id' => 24, 'discussion_id' => 12, 'created_at' => Carbon::now()->toDateTimeString(), 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p>Vlad b3noit Because when anyone creates new Window (by New Window menu), user will always expect, there will be a separate Window as per any native app. So when having separate Window (for unsaved windows), why in-place switching needed. Lots of users still prefer working with Windows (as this term is very obvious for any OS), sometime side-by-side, sometime by using external display.</p>

<p>Also any modern browser uses Tab-Groups for in-place switching, but they treat traditional Windows in same way. So instead of breaking Native terminology and flow of OS, we preferred to continue with Windows.</p>

<p>Also if some user still feels to use all in-place switching, then that user can create new Windows as new in-place group by clicking "+" from window manager &#128578;</p></t>'],
            ['id' => 23, 'discussion_id' => 12, 'created_at' => Carbon::now()->toDateTimeString(), 'user_id' => 1, 'type' => 'comment', 'content' => "<t><p>dino Vlad My thing about the solution of 'well, make a new saved window that does participate in in-place switching' is that:</p>

<p>there's no convenient affordance to move tabs from a visible, non-saved window to a saved window<br/>
there's no convenient affordance to simply transform a non-saved, visible window to a saved window that can interact with in-place switching<br/>
I don't see a reason why a user can't opt in a window into being able to be switched in-place<br/>
From where I'm standing, unless I'm missing something, the abstraction of a window and a group of tabs are two different things; it appears that in the case of Orion, there's an assumption about the relationship between a collection of tabs and windows such that, just like in programming languages with different 'colored' functions (e.g. async in JS, C#, etc), here you have the case of 'colored' windows.</p></t>"],

            ['id' => 22, 'discussion_id' => 11, 'created_at' => Carbon::now()->toDateTimeString(), 'user_id' => 1, 'type' => 'comment', 'content' => "<t><p>No and actually it's OK I found it. It was some corrupted pref files. The ones relating to the web icons. I had to reestablish some of my preferences for the app but otherwise not much hassle!</p>

</t>"],
            ['id' => 21, 'discussion_id' => 11, 'created_at' => Carbon::now()->toDateTimeString(), 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p>can u provide a video?</p>

</t>'],
            ['id' => 20, 'discussion_id' => 11, 'created_at' => Carbon::now()->toDateTimeString(), 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p>Steps to reproduce:<br/>
&lt;Include steps to reproduce the bug; Did you try using Compatibility mode? If applicable, does Safari behave in the same way?&gt;</p>

<p>Expected behavior:<br/>
&lt;What you expected to happen?&gt;</p>

<p>Orion, OS version; hardware type:<br/>
Orion version Version 0.99.121-beta (WebKit 614.1.20)<br/>
MacOS version 12.6 (21G115) Monterey<br/>
M1 Mac mini (M1, 2020)<br/>
Image/Video:<br/>
&lt;Copy/paste or drag and drop to upload images or videos (up to 20MB)&gt;</p>

<p>The bookmarks in my Orion usually show favicons of the sites after I visit them. Today even after clearing cache and restarting the browser several times they are still flashing. Any ideas?</p></t>'],

            ['id' => 19, 'discussion_id' => 10, 'created_at' => Carbon::now()->toDateTimeString(), 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p>Steps to reproduce:<br/>
&lt;Include steps to reproduce the bug; Did you try using Compatibility mode? If applicable, does Safari behave in the same way?&gt;</p>

<p>Expected behavior:<br/>
Position is saved.</p>

<p>Orion and macOS:<br/>
&lt;Version&gt;</p>

<p>Image/Video:</p></t>'],

            ['id' => 18, 'discussion_id' => 9, 'created_at' => Carbon::now()->toDateTimeString(), 'user_id' => 1, 'type' => 'comment', 'content' => "<t><p>Steps to reproduce:</p>

<p>Have one open window.<br/>
Have a saved window.<br/>
Using the toolbar icon, move the regular window to the saved window<br/>
Expected behavior:<br/>
Window is moved to saved area, doesn't affect other saved windows.<br/>
Orion, OS version; hardware type:<br/>
Version 0.99.123.1-beta (WebKit 615.1.16.1)<br/>
MacBook Pro (macOS Monterey 12.6.2 build 21G320)</p>

<p>Image/Video:</p>

</t>"],

            ['id' => 17, 'discussion_id' => 8, 'created_at' => Carbon::now()->toDateTimeString(), 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p>b3noit Can you please record a video of this?</p></t>'],
            ['id' => 16, 'discussion_id' => 8, 'created_at' => Carbon::now()->toDateTimeString(), 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p>Steps to reproduce:</p>

<p>Initialized a new non-saved window one tab<br/>
Initialize a new saved window with one tab<br/>
Switch in-place to saved window<br/>
Right tab and select move tab to New Window<br/>
Tab moves to non-saved window and replaces previously open tab in non-saved window<br/>
Expected behavior:<br/>
Tab should not replace non-saved window tab</p>

<p>Orion, OS version; hardware type:<br/>
0.99.123 on 12.6.3</p></t>'],
        ]);
    }

    /**
     * @inheritDoc
     */
    protected function tearDown(): void
    {
        parent::tearDown();

        $this->database()->table('discussions')->delete();
        $this->database()->table('posts')->delete();
    }

    /**
     * @test
     */
    public function can_search_for_word_or_title_in_post()
    {
        $response = $this->send(
            $this->request('GET', '/api/discussions')
                ->withQueryParams([
                    'filter' => ['q' => 'lightsail'],
                    'include' => 'mostRelevantPost',
                ])
        );

        $data = json_decode($response->getBody()->getContents(), true);
        $ids = array_map(function ($row) {
            return $row['id'];
        }, $data['data']);

        $this->assertEqualsCanonicalizing(['2', '1', '3'], $ids, 'IDs do not match');
    }

    /**
     * @test
     */
    public function search_prioritizes_title_search_score_over_post()
    {
        $response = $this->send(
            $this->request('GET', '/api/discussions')
                ->withQueryParams([
                    'filter' => ['q' => 'saved bookmarks'],
                    'include' => 'mostRelevantPost',
                ])
        );

        $data = json_decode($response->getBody()->getContents(), true);
        $ids = array_map(function ($row) {
            return $row['id'];
        }, $data['data']);

        $this->assertEquals(['11', '10', '7', '8', '9', '12'], $ids, 'IDs do not match');
    }

    /**
     * @test
     */
    public function ignores_non_word_characters_when_searching()
    {
        $response = $this->send(
            $this->request('GET', '/api/discussions')
                ->withQueryParams([
                    'filter' => ['q' => 'lightsail+'],
                    'include' => 'mostRelevantPost',
                ])
        );

        $data = json_decode($response->getBody()->getContents(), true);
        $ids = array_map(function ($row) {
            return $row['id'];
        }, $data['data']);

        $this->assertEqualsCanonicalizing(['2', '1', '3'], $ids, 'IDs do not match');
    }

    /**
     * @test
     */
    public function can_search_telugu_like_languages()
    {
        $response = $this->send(
            $this->request('GET', '/api/discussions')
                ->withQueryParams([
                    'filter' => ['q' => 'తెలుగు'],
                    'include' => 'mostRelevantPost',
                ])
        );

        $data = json_decode($response->getBody()->getContents(), true);
        $ids = array_map(function ($row) {
            return $row['id'];
        }, $data['data']);

        $this->assertEqualsCanonicalizing(['4', '5'], $ids, 'IDs do not match');
        $this->assertEqualsCanonicalizing(['6'], Arr::pluck($data['included'], 'id'));
    }

    /**
     * @test
     */
    public function can_search_cjk_languages()
    {
        $response = $this->send(
            $this->request('GET', '/api/discussions')
                ->withQueryParams([
                    'filter' => ['q' => '支持中文吗'],
                    'include' => 'mostRelevantPost',
                ])
        );

        $data = json_decode($response->getBody()->getContents(), true);
        $ids = array_map(function ($row) {
            return $row['id'];
        }, $data['data']);

        $this->assertEqualsCanonicalizing(['2', '6'], $ids, 'IDs do not match');
        $this->assertEqualsCanonicalizing(['7'], Arr::pluck($data['included'], 'id'));
    }

    /**
     * @test
     */
    public function search_for_special_characters_gives_empty_result()
    {
        $response = $this->send(
            $this->request('GET', '/api/discussions')
                ->withQueryParams([
                    'filter' => ['q' => '*'],
                    'include' => 'mostRelevantPost',
                ])
        );

        $data = json_decode($response->getBody()->getContents(), true);
        $this->assertEquals([], $data['data']);

        $response = $this->send(
            $this->request('GET', '/api/discussions')
                ->withQueryParams([
                    'filter' => ['q' => '@'],
                    'include' => 'mostRelevantPost',
                ])
        );

        $data = json_decode($response->getBody()->getContents(), true);
        $this->assertEquals([], $data['data']);
    }
}
