<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Mcp\Overview;

use Flarum\Extension\ExtensionManager;
use Flarum\Foundation\Application;
use Flarum\Mcp\Api\ResourceExposure;
use Flarum\Mcp\Capability\CapabilityRegistry;
use Flarum\Mcp\Capability\McpContext;
use Flarum\Mcp\Capability\ResolvedCapabilities;
use Flarum\User\User;

/**
 * The forum overview (`flarum://forum`) and the server instructions derived
 * from it: the forum, the actor, and what each enabled extension adds.
 */
class OverviewBuilder
{
    public const int INSTRUCTIONS_LENGTH = 2000;

    /**
     * Global abilities every overview reports on, with their labels.
     *
     * @var array<string, string>
     */
    protected const array ABILITIES = [
        'viewForum' => 'View the forum',
        'startDiscussion' => 'Start discussions',
        'discussion.reply' => 'Reply to discussions',
        'searchUsers' => 'Search users',
    ];

    public const string GUIDANCE = 'Prefer the dedicated tools (search_discussions, read_discussion, start_discussion, reply_to_discussion, ...) for everyday tasks. '
        .'For anything else, call describe_api to see the available resource types, then use list_resources, get_resource and the other generic tools. '
        .'Every action runs as the connected user, with exactly their permissions. '
        .'Write tools change the real forum and are seen by other members: confirm with the user before posting or editing. '
        .'Content is written in the forum\'s formatting syntax (usually Markdown).';

    public function __construct(
        protected ExtensionManager $extensions,
        protected CapabilityRegistry $registry,
        protected ResourceExposure $exposure,
        protected ExtensionOwnership $ownership,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function build(McpContext $context, ResolvedCapabilities $capabilities): array
    {
        $actor = $context->actor;

        $types = [];
        foreach ($this->exposure->exposed($actor) as $type => $operations) {
            $resource = $this->exposure->resource($type);

            if ($resource) {
                $types[$this->ownership->owner($resource)][] = $type;
            }
        }

        $instructions = $this->extensionInstructions($context);

        $extensions = [];
        foreach ($this->extensions->getEnabledExtensions() as $id => $extension) {
            $extensions[] = [
                'id' => $id,
                'title' => $extension->getTitle(),
                'description' => (string) $extension->composerJsonAttribute('description'),
                'version' => $extension->getVersion(),
                ...$capabilities->contributedBy((string) $id),
                'types' => $types[$id] ?? [],
                'instructions' => $instructions[$id] ?? null,
            ];
        }

        return [
            'forum' => [
                'title' => (string) $context->settings->get('forum_title'),
                'description' => (string) $context->settings->get('forum_description'),
                'url' => $context->forumUrl(),
                'locale' => $context->translator->getLocale(),
                'version' => Application::VERSION,
            ],
            'you' => $this->you($actor),
            'core' => array_filter([
                'types' => $types[ExtensionOwnership::CORE] ?? [],
                'instructions' => $instructions[ExtensionOwnership::CORE] ?? null,
            ], fn ($value) => $value !== null),
            'extensions' => $extensions,
            'guidance' => static::GUIDANCE,
        ];
    }

    /**
     * Short Markdown for the server `instructions` field.
     */
    public function instructions(McpContext $context, ResolvedCapabilities $capabilities): string
    {
        $overview = $this->build($context, $capabilities);
        $forum = $overview['forum'];
        $you = $overview['you'];

        $head = "# {$forum['title']}\n";
        if ($forum['description'] !== '') {
            $head .= "{$forum['description']}\n";
        }

        $head .= $you['guest']
            ? "\nYou are connected as a guest: you can read what guests can see.\n"
            : "\nYou are connected as {$you['displayName']} (@{$you['username']}).\n";

        $tail = "\n".static::GUIDANCE."\nThe flarum://forum resource has the full overview.";

        $lines = [];
        $notes = [];
        foreach ($overview['extensions'] as $extension) {
            $line = "- {$extension['title']}";
            if ($extension['description'] !== '') {
                $line .= ": {$extension['description']}";
            }
            $lines[] = $line;

            if ($extension['instructions']) {
                $notes[] = "- {$extension['title']}: {$extension['instructions']}";
            }
        }

        if (! empty($overview['core']['instructions'])) {
            array_unshift($notes, "- {$overview['core']['instructions']}");
        }

        $body = $lines ? "\nEnabled extensions:\n".implode("\n", $lines)."\n" : '';
        if ($notes) {
            $body .= "\nNotes:\n".implode("\n", $notes)."\n";
        }

        $room = static::INSTRUCTIONS_LENGTH - mb_strlen($head) - mb_strlen($tail);
        if (mb_strlen($body) > $room) {
            $body = rtrim(mb_substr($body, 0, max(0, $room - 2)))."\n…\n";
        }

        return mb_substr($head.$body.$tail, 0, static::INSTRUCTIONS_LENGTH);
    }

    /**
     * @return array<string, mixed>
     */
    public function you(User $actor): array
    {
        $can = [];
        foreach ([...static::ABILITIES, ...$this->registry->abilities()] as $ability => $label) {
            // `discussion.reply` is checked globally: tags may still restrict it per tag.
            $allowed = $ability === 'discussion.reply'
                ? $actor->hasPermission($ability)
                : $actor->can($ability);

            if ($allowed) {
                $can[] = ['ability' => $ability, 'label' => $label];
            }
        }

        return [
            'guest' => $actor->isGuest(),
            'username' => $actor->isGuest() ? null : $actor->username,
            'displayName' => $actor->isGuest() ? null : $actor->display_name,
            'groups' => $actor->isGuest() ? [] : $actor->groups->pluck('name_singular')->values()->all(),
            'can' => $can,
        ];
    }

    /**
     * @return array<string, string> extension ID => instructions
     */
    protected function extensionInstructions(McpContext $context): array
    {
        $instructions = [];

        foreach ($this->registry->instructions() as $entry) {
            $text = $entry['instructions'];

            // A string is never called, even when it happens to name a function.
            if (! is_string($text)) {
                $text = $text($context);
            }

            if (! is_string($text) || trim($text) === '') {
                continue;
            }

            $id = $entry['extension'] ?? ExtensionOwnership::CORE;
            $instructions[$id] = trim(($instructions[$id] ?? '').' '.trim($text));
        }

        return $instructions;
    }
}
