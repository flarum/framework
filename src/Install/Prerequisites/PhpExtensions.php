<?php
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Install\Prerequisites;

class PhpExtensions extends AbstractPrerequisite
{
    public function check()
    {
        foreach (['mbstring', 'pdo_mysql', 'openssl', 'json', 'gd', 'dom', 'fileinfo'] as $extension) {
            if (! extension_loaded($extension)) {
                $this->errors[] = [
                    'message' => "The $extension extension is required.",
                ];
            }
        }
    }
}
