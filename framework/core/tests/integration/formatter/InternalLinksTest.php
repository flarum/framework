<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\formatter;

use Flarum\Formatter\Formatter;
use Flarum\Testing\integration\RefreshesFormatterCache;
use Flarum\Testing\integration\TestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Links back to the forum itself are not the same as links off it.
 *
 * A link somewhere else is untrusted: it gets `ugc` and `nofollow` so that a
 * forum cannot be used to pass ranking signals to whatever someone pastes. A
 * link to another discussion on the same forum is the forum's own content, and
 * telling search engines to ignore it means a discussion linked from another
 * discussion counts for nothing.
 *
 * The distinction is made when the post is rendered rather than when it is
 * written, so that posts written before this existed behave the same way, and
 * so that moving a forum to a new address does not leave its old posts full of
 * links it no longer recognises as its own.
 */
class InternalLinksTest extends TestCase
{
    use RefreshesFormatterCache;

    protected function setUp(): void
    {
        parent::setUp();

        // The address the forum believes it lives at, which is what every link
        // is measured against.
        $this->config('url', 'http://flarum.localhost');
    }

    protected function render(string $text): string
    {
        $formatter = $this->app()->getContainer()->make(Formatter::class);

        return $formatter->render($formatter->parse($text));
    }

    #[Test]
    public function oversized_discussion_ids_are_not_sent_to_the_database()
    {
        $formatter = $this->app()->getContainer()->make(Formatter::class);
        $xml = $formatter->parse('http://flarum.localhost/d/999999999999999999999999999999');
        $db = $this->app()->getContainer()->make('db')->connection();
        $db->enableQueryLog();
        $db->flushQueryLog();
        $html = $formatter->render($xml);

        $this->assertStringContainsString('#999999999999999999999999999999', $html);
        $this->assertSame([], $db->getQueryLog());
    }

    #[Test]
    public function an_empty_discussion_title_keeps_the_numeric_label()
    {
        $this->prepareDatabase(['discussions' => [
            ['id' => 123, 'title' => '', 'slug' => 'empty', 'created_at' => '2026-01-01 00:00:00'],
        ]]);

        $this->assertStringContainsString('#123', $this->render('http://flarum.localhost/d/123'));
    }

    #[Test]
    public function a_request_without_an_actor_uses_guest_visibility()
    {
        $formatter = $this->app()->getContainer()->make(Formatter::class);
        $html = $formatter->render($formatter->parse('http://flarum.localhost/d/123'), null, new \Laminas\Diactoros\ServerRequest());

        $this->assertStringContainsString('#123', $html);
    }

    #[Test]
    public function an_author_cannot_supply_a_discussion_title_attribute()
    {
        $this->extension('flarum-bbcode');
        $html = $this->render('[url=http://flarum.localhost/d/123 discussiontitle="Forged title"]http://flarum.localhost/d/123[/url]');

        $this->assertStringNotContainsString('Forged title', $html);
        $this->assertStringContainsString('#123', $html);
    }

    #[Test]
    public function titles_follow_the_viewer_and_are_not_retained_for_requestless_renders()
    {
        $this->prepareDatabase(['discussions' => [
            ['id' => 123, 'title' => 'Restricted title', 'slug' => 'restricted', 'created_at' => '2026-01-01 00:00:00', 'hidden_at' => '2026-01-01 00:00:00'],
        ]]);
        $formatter = $this->app()->getContainer()->make(Formatter::class);
        $xml = $formatter->parse('http://flarum.localhost/d/123');
        $request = \Flarum\Http\RequestUtil::withActor(new \Laminas\Diactoros\ServerRequest(), \Flarum\User\User::findOrFail(1));

        $this->assertStringContainsString('>Restricted title</span>', $formatter->render($xml, null, $request));
        $html = $formatter->render($xml);
        $this->assertStringNotContainsString('Restricted title', $html);
        $this->assertStringContainsString('#123', $html);
    }

    #[Test]
    public function a_quoted_discussion_link_keeps_its_title_and_address()
    {
        $this->extension('flarum-markdown');
        $this->prepareDatabase(['discussions' => [
            ['id' => 123, 'title' => 'Quoted discussion', 'slug' => 'quoted', 'created_at' => '2026-01-01 00:00:00'],
        ]]);

        $html = $this->render('> http://flarum.localhost/d/123');

        $this->assertStringContainsString('<blockquote>', $html);
        $this->assertStringContainsString('>Quoted discussion</span>', $html);
        $this->assertStringContainsString('href="http://flarum.localhost/d/123"', $html);
    }

    #[Test]
    public function an_html_email_template_can_render_a_public_discussion_title()
    {
        $this->prepareDatabase(['discussions' => [
            ['id' => 123, 'title' => 'Email discussion', 'slug' => 'email', 'created_at' => '2026-01-01 00:00:00'],
        ]]);
        $preview = $this->app()->getContainer()->make(\Flarum\Mail\MailFormatter::class)->convert('http://flarum.localhost/d/123');
        $view = $this->app()->getContainer()->make(\Illuminate\Contracts\View\Factory::class);
        $view->share(['username' => 'Recipient']);
        $html = $view->make('mail::html.notification', [
            'preview' => $preview,
            'user' => (object) ['email' => 'recipient@example.com'],
            'unsubscribeLink' => 'http://flarum.localhost/unsubscribe',
            'settingsLink' => 'http://flarum.localhost/settings',
            'type' => 'test', 'forumTitle' => 'Forum', 'username' => 'Recipient',
            'userEmail' => 'recipient@example.com', 'title' => 'Notification',
        ])->render();

        $this->assertStringContainsString('>Email discussion</span>', $html);
        $this->assertStringContainsString('href="http://flarum.localhost/d/123"', $html);
    }

    #[Test]
    public function a_visible_discussion_link_shows_its_title_without_a_request()
    {
        $this->prepareDatabase(['discussions' => [
            ['id' => 123, 'title' => 'A title & more', 'slug' => 'a-title', 'created_at' => '2026-01-01 00:00:00'],
        ]]);

        $html = $this->render('http://flarum.localhost/d/123-a-title/4');

        $this->assertStringContainsString('>A title &amp; more</span>', $html);
        $this->assertStringContainsString('>4<', $html);
    }

    #[Test]
    public function a_link_to_another_site_is_not_followed()
    {
        $html = $this->render('https://example.com/some-page');

        $this->assertStringContainsString('nofollow', $html);
        $this->assertStringContainsString('ugc', $html);
    }

    #[Test]
    public function a_link_back_to_the_forum_is_followed()
    {
        $html = $this->render('http://flarum.localhost/d/1-a-discussion');

        $this->assertStringNotContainsString('nofollow', $html);
        $this->assertStringNotContainsString('ugc', $html);
    }

    #[Test]
    public function a_link_back_to_the_forum_still_says_noopener()
    {
        // Nothing about trusting our own content makes it safe to hand the
        // opener window to whatever the link opens.
        $this->assertStringContainsString('noopener', $this->render('http://flarum.localhost/d/1'));
    }

    #[Test]
    public function a_link_back_to_the_forum_opens_in_the_same_tab()
    {
        $this->assertStringContainsString('target="_self"', $this->render('http://flarum.localhost/d/1'));
    }

    #[Test]
    public function a_link_back_to_the_forum_is_marked_for_the_frontend()
    {
        // The marker is what lets the forum route to the link rather than
        // reloading the whole page to reach its own content.
        $this->assertStringContainsString('UrlLink--internal', $this->render('http://flarum.localhost/d/1'));
    }

    #[Test]
    public function a_link_to_another_site_is_not_marked_as_internal()
    {
        $this->assertStringNotContainsString('UrlLink--internal', $this->render('https://example.com'));
    }

    #[Test]
    public function a_different_host_that_merely_starts_the_same_is_not_ours()
    {
        // flarum.localhost.evil.com is not flarum.localhost, and a prefix match
        // would hand an attacker a followed link.
        $html = $this->render('http://flarum.localhost.evil.com/d/1');

        $this->assertStringContainsString('nofollow', $html);
        $this->assertStringNotContainsString('UrlLink--internal', $html);
    }

    #[Test]
    public function a_link_over_a_different_scheme_is_still_ours()
    {
        // A forum served over https that someone linked with http is the same
        // forum; the address bar simply disagrees with the config.
        $html = $this->render('https://flarum.localhost/d/1');

        $this->assertStringNotContainsString('nofollow', $html);
        $this->assertStringContainsString('UrlLink--internal', $html);
    }

    #[Test]
    public function a_subdomain_is_not_the_forum()
    {
        $html = $this->render('http://blog.flarum.localhost/d/1');

        $this->assertStringContainsString('nofollow', $html);
        $this->assertStringNotContainsString('UrlLink--internal', $html);
    }

    #[Test]
    public function a_relative_link_is_ours()
    {
        // `[text](/d/123)` is how people link within a forum most of the time.
        // Treating it as external told search engines not to follow a forum's
        // own links — the very thing this is meant to stop.
        $this->extension('flarum-markdown');

        $html = $this->render('[FriendsOfFlarum OAuth](/d/25182)');

        $this->assertStringNotContainsString('nofollow', $html);
        $this->assertStringContainsString('UrlLink--internal', $html);
    }

    #[Test]
    public function a_relative_link_outside_the_discussion_routes_is_still_ours()
    {
        $this->extension('flarum-markdown');

        $this->assertStringNotContainsString('nofollow', $this->render('[settings](/settings)'));
    }

    #[Test]
    public function a_protocol_relative_link_is_not_ours()
    {
        // `//evil.com/d/1` has no scheme but is absolute. Read as a path it
        // would look like this forum's own, which is how you would smuggle a
        // followed link past this.
        $this->extension('flarum-markdown');

        $html = $this->render('[evil](//evil.com/d/1)');

        $this->assertStringContainsString('nofollow', $html);
        $this->assertStringNotContainsString('UrlLink--internal', $html);
    }

    #[Test]
    public function a_path_relative_link_is_left_alone()
    {
        // `d/1` resolves against whichever page it is read on, and a post can
        // be read from more than one place, so where it leads is not knowable
        // while rendering.
        $this->extension('flarum-markdown');

        $this->assertStringContainsString('nofollow', $this->render('[rel](d/1)'));
    }

    #[Test]
    public function a_mailto_link_is_not_claimed_as_ours()
    {
        $this->extension('flarum-markdown');

        $html = $this->render('[mail](mailto:someone@example.com)');

        $this->assertStringNotContainsString('UrlLink--internal', $html);
    }

    #[Test]
    public function a_discussion_link_is_shown_as_the_discussion_it_points_at()
    {
        // A pasted address is unreadable in a sentence. What the reader cares
        // about is which discussion it is, so the link says that instead.
        $html = $this->render('http://flarum.localhost/d/123-the-slug');

        $this->assertStringContainsString('#123', $html);

        // The slug stays in the address — the link still has to work — but is
        // no longer part of what the reader sees.
        $this->assertStringContainsString('href="http://flarum.localhost/d/123-the-slug"', $html);
        $this->assertStringNotContainsString('>http://flarum.localhost/d/123-the-slug<', $html);
    }

    #[Test]
    public function a_link_to_a_post_says_which_post()
    {
        $html = $this->render('http://flarum.localhost/d/123-the-slug/4');

        $this->assertStringContainsString('#123', $html);
        $this->assertStringContainsString('>4<', $html);
    }

    #[Test]
    public function a_discussion_link_carries_the_forums_favicon()
    {
        $this->setting('favicon_path', 'favicon-abc.png');

        $this->assertStringContainsString('favicon-abc.png', $this->render('http://flarum.localhost/d/123-the-slug'));
    }

    #[Test]
    public function a_discussion_link_without_a_favicon_still_reads_properly()
    {
        // Most forums never upload one. The label is the point; the icon is
        // decoration, and its absence must not leave a broken image behind.
        $html = $this->render('http://flarum.localhost/d/123-the-slug');

        $this->assertStringContainsString('#123', $html);
        $this->assertStringNotContainsString('<img', $html);
    }

    #[Test]
    public function an_internal_link_that_is_not_a_discussion_is_left_as_it_was()
    {
        // Only discussion links have a #123 to show. A link to a user page or
        // the tag index keeps its address as the visible text.
        $html = $this->render('http://flarum.localhost/u/ianm');

        $this->assertStringContainsString('http://flarum.localhost/u/ianm', $html);
        $this->assertStringContainsString('UrlLink--internal', $html);
    }

    #[Test]
    public function a_discussion_link_the_writer_gave_their_own_words_keeps_them()
    {
        // `[click here](url)` is the writer choosing the text. Replacing it
        // with #123 would overwrite what they wrote.
        $this->extension('flarum-markdown');

        $html = $this->render('[click here](http://flarum.localhost/d/123-the-slug)');

        $this->assertStringContainsString('click here', $html);
        $this->assertStringNotContainsString('#123', $html);
    }

    #[Test]
    public function a_bare_discussion_link_is_labelled_with_markdown_enabled_too()
    {
        // The label comes from the rendered XML rather than from how the link
        // was written, so enabling Markdown must not change the outcome.
        $this->extension('flarum-markdown');

        $html = $this->render('http://flarum.localhost/d/123-the-slug/4');

        $this->assertStringContainsString('#123', $html);
        $this->assertStringContainsString('>4<', $html);
    }

    #[Test]
    public function a_markdown_autolink_is_labelled()
    {
        // `<url>` is Markdown's own way of writing a bare link — the writer
        // supplied no text of their own, so the label applies.
        $this->extension('flarum-markdown');

        $this->assertStringContainsString('#123', $this->render('<http://flarum.localhost/d/123-the-slug>'));
    }

    #[Test]
    public function a_subclass_built_the_old_way_still_works()
    {
        // Extensions subclass Formatter and call parent::__construct() with the
        // two arguments it used to take — fof/user-bio does exactly this. The
        // forum's address has to be reachable without being handed in, or every
        // one of those extensions fatals on construction.
        $container = $this->app()->getContainer();

        $formatter = new class(new \Illuminate\Cache\Repository($container->make('cache.filestore')), $container->make(\Flarum\Foundation\Paths::class)->storage.'/formatter') extends Formatter {
        };

        $this->assertStringContainsString(
            'UrlLink--internal',
            $formatter->render($formatter->parse('http://flarum.localhost/d/1'))
        );
    }

    #[Test]
    public function a_rel_set_by_an_extension_is_left_alone()
    {
        // `configureDefaultsOnLinks` fills in what is missing rather than
        // overriding, and that has to stay true for internal links too.
        $this->extend(
            (new \Flarum\Extend\Formatter())
                ->render(function ($renderer, $context, string $xml) {
                    return \s9e\TextFormatter\Utils::replaceAttributes($xml, 'URL', function (array $attributes) {
                        $attributes['rel'] = 'author';

                        return $attributes;
                    });
                })
        );

        $this->assertStringContainsString('rel="author"', $this->render('http://flarum.localhost/d/1'));
    }
}
