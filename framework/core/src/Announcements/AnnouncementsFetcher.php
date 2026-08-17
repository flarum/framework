<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Announcements;

use Flarum\Foundation\Application;
use Flarum\Foundation\ApplicationInfoProvider;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Arr;
use RuntimeException;

class AnnouncementsFetcher
{
    private Client $client;

    public function __construct(
        protected ApplicationInfoProvider $appInfo
    ) {
        $this->client = new Client(['timeout' => 10]);
    }
    protected const API_BASE_URL = 'https://discuss.flarum.org/api/discussions';
    protected const TAG = 'blog';

    /**
     * Version tags whose news is only relevant to older lines. A discussion is
     * dropped from the feed when it carries one of these AND none of the tags in
     * {@see KEEP_VERSION_TAGS} — so a 1.x-only patch note is hidden, but a post
     * tagged for both 1.x and 2.x still comes through. Version-neutral posts
     * (Community Updates, announcements) carry neither and are always kept.
     *
     * This has to be applied in PHP: the discussions API's negated tag filter
     * excludes on the mere presence of a tag, so it can't express "1.x but not
     * also 2.x" and would drop the dual-tagged posts we want to keep.
     *
     * @var string[]
     */
    protected const EXCLUDE_VERSION_TAGS = ['version-1x'];

    /**
     * Version tags that rescue a discussion from exclusion — the current line's
     * news. A post tagged for both an excluded line and this one is kept.
     *
     * @var string[]
     */
    protected const KEEP_VERSION_TAGS = ['version-2x'];

    protected const LIMIT = 8;
    protected const FETCH_LIMIT = 20;
    protected const EXCERPT_LENGTH = 200;

    public function fetch(): array
    {
        $url = self::API_BASE_URL.'?'.http_build_query([
            'filter' => ['tag' => self::TAG],
            'sort' => '-createdAt',
            'page' => ['limit' => self::FETCH_LIMIT],
            'include' => 'firstPost,user,tags',
        ]);

        try {
            $response = $this->client->get($url, [
                'headers' => [
                    'Accept' => 'application/json',
                    'User-Agent' => 'Flarum/'.Application::VERSION
                        .' PHP/'.$this->appInfo->identifyPHPVersion()
                        .' Database/'.$this->appInfo->identifyDatabaseDriver().'/'.$this->appInfo->identifyDatabaseVersion(),
                ],
            ]);
        } catch (GuzzleException $e) {
            throw new RuntimeException('Could not fetch announcements from discuss.flarum.org: '.$e->getMessage(), 0, $e);
        }

        $body = json_decode((string) $response->getBody(), true);

        if (! is_array($body['data'] ?? null)) {
            throw new RuntimeException('Unexpected response from discuss.flarum.org.');
        }

        $posts = [];
        $users = [];
        $tagSlugs = [];
        foreach ($body['included'] ?? [] as $resource) {
            if (Arr::get($resource, 'type') === 'posts') {
                $posts[$resource['id']] = $resource;
            } elseif (Arr::get($resource, 'type') === 'users') {
                $users[$resource['id']] = $resource;
            } elseif (Arr::get($resource, 'type') === 'tags') {
                $tagSlugs[$resource['id']] = Arr::get($resource, 'attributes.slug');
            }
        }

        $items = [];
        foreach ($body['data'] as $discussion) {
            $id = Arr::get($discussion, 'id');
            $title = Arr::get($discussion, 'attributes.title');
            $slug = Arr::get($discussion, 'attributes.slug');
            $createdAt = Arr::get($discussion, 'attributes.createdAt');

            // Skip any discussion missing the fields we require to render a card.
            if (! $id || ! $title || ! $slug || ! $createdAt) {
                continue;
            }

            // Filter out news for older lines, keeping posts that also target the
            // current line (dual-tagged) and version-neutral posts.
            if ($this->isExcludedByVersion($discussion, $tagSlugs)) {
                continue;
            }

            $firstPostId = Arr::get($discussion, 'relationships.firstPost.data.id');
            $firstPost = $firstPostId ? ($posts[$firstPostId] ?? null) : null;

            $userId = Arr::get($discussion, 'relationships.user.data.id');
            $user = $userId ? ($users[$userId] ?? null) : null;

            $items[] = [
                'id' => $id,
                'title' => $title,
                'slug' => $slug,
                'commentCount' => Arr::get($discussion, 'attributes.commentCount', 0),
                'createdAt' => $createdAt,
                'isSticky' => (bool) Arr::get($discussion, 'attributes.isSticky', false),
                'url' => 'https://discuss.flarum.org/d/'.$slug,
                // Null where the post never arrived, as against an empty string
                // for a post that genuinely has no text. Collapsing the two hid
                // a real fault: discuss.flarum.org stopped serializing the
                // `firstPost` include, and every forum's announcements widget
                // rendered blank cards that read as "these posts are empty".
                'excerpt' => $firstPost === null
                    ? null
                    : $this->makeExcerpt(Arr::get($firstPost, 'attributes.contentHtml', '')),
                'authorName' => Arr::get($user, 'attributes.displayName'),
                'avatarUrl' => Arr::get($user, 'attributes.avatarUrl'),
            ];
        }

        usort($items, fn (array $a, array $b) => $b['isSticky'] <=> $a['isSticky']);

        return array_slice($items, 0, self::LIMIT);
    }

    /**
     * A discussion is excluded when it carries a tag for an older line and none
     * for the current one — 1.x-only news on a 2.x forum. Dual-tagged and
     * version-neutral posts are kept.
     *
     * @param array<string, mixed> $discussion
     * @param array<string, string|null> $tagSlugs  tag id => slug, from `included`
     */
    private function isExcludedByVersion(array $discussion, array $tagSlugs): bool
    {
        $slugs = [];
        foreach (Arr::get($discussion, 'relationships.tags.data', []) as $tag) {
            $slug = $tagSlugs[Arr::get($tag, 'id')] ?? null;

            if ($slug !== null) {
                $slugs[] = $slug;
            }
        }

        $hasExcluded = array_intersect($slugs, self::EXCLUDE_VERSION_TAGS) !== [];
        $hasKept = array_intersect($slugs, self::KEEP_VERSION_TAGS) !== [];

        return $hasExcluded && ! $hasKept;
    }

    private function makeExcerpt(string $html): string
    {
        $plain = strip_tags($html);
        $plain = trim(preg_replace('/\s+/', ' ', $plain));

        return mb_strimwidth($plain, 0, self::EXCERPT_LENGTH, '…');
    }
}
