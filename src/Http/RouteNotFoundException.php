<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Http;

use Exception;

class RouteNotFoundException extends Exception
{
    public function __construct($message = "", $code = 404, Exception $previous = null)
    {
        // Pass the message and integer code to the parent
        parent::__construct($message, (int)$code, $previous);
		// @link http://bugs.php.net/39615 Save the unmodified code
		$this->code = $code;
    }
}
