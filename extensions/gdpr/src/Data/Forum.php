<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Gdpr\Data;

use Carbon\Carbon;
use Flarum\Gdpr\ZipManager;

class Forum extends Type
{
    public function export(): ?array
    {
        $forumTitle = $this->settings->get('forum_title');
        $url = $this->url->to('forum')->base();

        $comment = $this->translator->trans('flarum-gdpr.forum.export_file', [
            '{forumTitle}' => $forumTitle,
            '{url}'        => $url,
            '{username}'   => $this->user->username,
            '{email}'      => $this->user->email,
            '{date}'       => Carbon::now()->toDateTimeString(),
        ]);

        resolve(ZipManager::class)->setComment($comment);

        return ["{$forumTitle}-{$this->user->username}.txt" => $comment];
    }

    public function anonymize(): void
    {
        // Nothing to do
    }

    public static function anonymizeDescription(): string
    {
        return self::deleteDescription();
    }

    public function delete(): void
    {
        // Nothing to do
    }

    public static function deleteDescription(): string
    {
        return static::staticTranslator()->trans('flarum-gdpr.lib.data.no_action');
    }
}
