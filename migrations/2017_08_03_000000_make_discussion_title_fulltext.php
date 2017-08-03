<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

return [
    'up' => function (Builder $schema) {
        $prefix = $schema->getConnection()->getTablePrefix();
        $schema->getConnection()->statement('ALTER TABLE '.$prefix.'discussions ADD FULLTEXT(`title`)');
    }
];
