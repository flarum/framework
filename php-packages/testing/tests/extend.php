<?php

/*
 * This file is part of flarum/testing-tests.
 *
 * Copyright (c) 2021 .
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace Flarum\Testing;

use Flarum\Extend;

return [
    (new Extend\Settings)->serializeToForum('notARealSetting', 'not.a.real.setting')
];
