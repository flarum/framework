<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Mentions\Formatter;

use Flarum\Http\SlugManager;
use Flarum\Locale\TranslatorInterface;
use Flarum\User\User;
use s9e\TextFormatter\Renderer;
use s9e\TextFormatter\Utils;

class FormatUserMentions
{
    use LooksUpMentionedModels;

    public function __construct(
        private readonly TranslatorInterface $translator,
        private readonly SlugManager $slugManager
    ) {
    }

    public function __invoke(Renderer $renderer, mixed $context, string $xml): string
    {
        $users = $this->mentionedModels($context, 'mentionsUsers', $xml, 'USERMENTION', User::query());

        return Utils::replaceAttributes($xml, 'USERMENTION', function ($attributes) use ($users) {
            /** @var User|null $user */
            $user = $users->find($attributes['id']);

            $attributes['deleted'] = false;

            if ($user) {
                $attributes['slug'] = $this->slugManager->forResource(User::class)->toSlug($user);
                $attributes['displayname'] = $user->display_name;
            } else {
                $attributes['deleted'] = true;
                $attributes['slug'] = '';
                $attributes['displayname'] = $this->translator->trans('core.lib.username.deleted_text');
            }

            return $attributes;
        });
    }
}
