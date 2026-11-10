<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Frontend\Event;

/**
 * Dispatched after the frontend assets have been recompiled in place (e.g. an
 * extension was toggled or the cache was cleared), so the compiled JS/CSS now has
 * a new revision. Lets consumers react — for example, to notify connected clients
 * that the assets they loaded are out of date.
 */
class AssetsRecompiled
{
}
