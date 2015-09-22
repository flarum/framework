<?php
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Flags\Listeners;

use Flarum\Events\RegisterLocales;
use Flarum\Events\BuildClientView;
use Illuminate\Contracts\Events\Dispatcher;

class AddClientAssets
{
    public function subscribe(Dispatcher $events)
    {
        $events->listen(RegisterLocales::class, [$this, 'addLocale']);
        $events->listen(BuildClientView::class, [$this, 'addAssets']);
    }

    public function addLocale(RegisterLocales $event)
    {
        $event->addTranslations('en', __DIR__.'/../../locale/en.yml');
    }

    public function addAssets(BuildClientView $event)
    {
        $event->forumAssets([
            __DIR__.'/../../js/forum/dist/extension.js',
            __DIR__.'/../../less/forum/extension.less'
        ]);

        $event->forumBootstrapper('flags/main');

        $event->forumTranslations([
            'flags.reason_off_topic',
            'flags.reason_spam',
            'flags.reason_inappropriate',
            'flags.reason_other',
            'flags.flagged_by',
            'flags.flagged_by_with_reason',
            'flags.no_flags'
        ]);

        $event->adminAssets([
            __DIR__.'/../../js/admin/dist/extension.js',
            __DIR__.'/../../less/admin/extension.less'
        ]);

        $event->adminBootstrapper('flags/main');

        $event->adminTranslations([
            // 'flag.hello_world'
        ]);
    }
}
