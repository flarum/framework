<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Http\Exception;

use Exception;
use Symfony\Component\Translation\TranslatorInterface;

class CustomException extends Exception
{
    public $view;

    public function __construct($message, $code, Exception $previous = null)
    {
        $this->view = 'flarum.forum::error.custom';

        parent::__construct(
            app(TranslatorInterface::class)->trans($message),
            $code,
            $previous
        );
    }
}
