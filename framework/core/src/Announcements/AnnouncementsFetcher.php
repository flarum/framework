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
    protected const LIMIT = 8;
    protected const FETCH_LIMIT = 20;
    protected const EXCERPT_LENGTH = 200;

    public function fetch(): array
    {
        $url = self::API_BASE_URL.'?'.http_build_query([
            'filter' => ['tag' => self::TAG],
            'sort' => '-createdAt',
            'page' => ['limit' => self::FETCH_LIMIT],
            'include' => 'firstPost,user',
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
        foreach ($body['included'] ?? [] as $resource) {
            if (Arr::get($resource, 'type') === 'posts') {
                $posts[$resource['id']] = $resource;
            } elseif (Arr::get($resource, 'type') === 'users') {
                $users[$resource['id']] = $resource;
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
                'excerpt' => $this->makeExcerpt(Arr::get($firstPost, 'attributes.contentHtml', '')),
                'authorName' => Arr::get($user, 'attributes.displayName'),
                'avatarUrl' => Arr::get($user, 'attributes.avatarUrl'),
            ];
        }

        usort($items, fn (array $a, array $b) => $b['isSticky'] <=> $a['isSticky']);

        return array_slice($items, 0, self::LIMIT);
    }

    private function makeExcerpt(string $html): string
    {
        $plain = strip_tags($html);
        $plain = trim(preg_replace('/\s+/', ' ', $plain));

        return mb_strimwidth($plain, 0, self::EXCERPT_LENGTH, '…');
    }
}
