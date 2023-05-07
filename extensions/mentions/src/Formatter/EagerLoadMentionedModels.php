<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Mentions\Formatter;

use Flarum\Extension\ExtensionManager;
use Flarum\Group\GroupRepository;
use Flarum\Mentions\ConfigureMentions;
use Flarum\Post\PostRepository;
use Flarum\Tags\TagRepository;
use Flarum\User\User;
use Illuminate\Database\Eloquent\Collection;
use s9e\TextFormatter\Parser;

class EagerLoadMentionedModels
{
    /**
     * @var ExtensionManager
     */
    protected $extensions;

    /**
     * @var PostRepository
     */
    protected $posts;

    /**
     * @var GroupRepository
     */
    protected $groups;

    /**
     * @var TagRepository
     */
    protected $tags;

    public function __construct(ExtensionManager $extensions, PostRepository $posts, GroupRepository $groups, TagRepository $tags)
    {
        $this->extensions = $extensions;
        $this->posts = $posts;
        $this->groups = $groups;
        $this->tags = $tags;
    }

    /**
     * @param mixed|\Flarum\Post\CommentPost|null $context
     */
    public function __invoke(Parser $parser, $context, string $text, ?User $actor): string
    {
        $callables = $this->getEagerLoaders();

        $parser->registeredVars['mentions'] = [];

        foreach ($callables as $modelType => $callable) {
            $parser->registeredVars['mentions'][$modelType] = $callable($text, $actor);
        }

        return $text;
    }

    protected function getEagerLoaders(): array
    {
        $callables = [
            'users' => [$this, 'eagerLoadUserMentions'],
            'posts' => [$this, 'eagerLoadPostMentions'],
            'groups' => [$this, 'eagerLoadGroupMentions'],
        ];

        if ($this->extensions->isEnabled('flarum-tags')) {
            $callables['tags'] = [$this, 'eagerLoadTagMentions'];
        }

        return $callables;
    }

    protected function eagerLoadUserMentions(string $text, ?User $actor): Collection
    {
        preg_match_all(ConfigureMentions::USER_MENTION_WITH_USERNAME_REGEX, $text, $usernameMatches);
        preg_match_all(ConfigureMentions::USER_MENTION_WITH_DISPLAY_NAME_REGEX, $text, $idMatches);

        return User::query()
            ->whereIn('username', $usernameMatches['username'])
            ->orWhereIn('id', $idMatches['id'])
            ->get();
    }

    protected function eagerLoadPostMentions(string $text, ?User $actor): Collection
    {
        preg_match_all(ConfigureMentions::POST_MENTION_WITH_DISPLAY_NAME_REGEX, $text, $matches);

        return $this->posts
            ->queryVisibleTo($actor)
            ->findMany($matches['id']);
    }

    protected function eagerLoadGroupMentions(string $text, ?User $actor): Collection
    {
        preg_match_all(ConfigureMentions::GROUP_MENTION_WITH_NAME_REGEX, $text, $matches);

        return $this->groups
            ->queryVisibleTo($actor)
            ->findMany($matches['id']);
    }

    protected function eagerLoadTagMentions(string $text, ?User $actor): Collection
    {
        preg_match_all(ConfigureMentions::TAG_MENTION_WITH_SLUG_REGEX, $text, $matches);

        return $this->tags
            ->queryVisibleTo($actor)
            ->whereIn('slug', $matches['slug'])
            ->get();
    }
}
