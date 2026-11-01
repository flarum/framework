<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Foundation\IconImage;

use Flarum\Foundation\AbstractImageValidator;

/**
 * Raster images only: an SVG served from the forum's own domain could run
 * script, and GD cannot read it.
 */
class IconImageValidator extends AbstractImageValidator
{
}
