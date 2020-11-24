<?php


namespace Flarum\Nicknames;

use Flarum\Event\ConfigureUserGambits;

class SetUserNicknameGambit
{
    public function handle(ConfigureUserGambits $event) {
        $event->gambits->setFulltextGambit(NicknameFullTextGambit::class);
    }
}
