<?php
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Core\Validator;

use League\Flysystem\FilesystemInterface;

class AvatarValidator extends AbstractValidator
{
    protected $rules = [
        'avatar' => [
            'required',
            'image',
            'max:2048'
        ]
    ];
    
    public function assertValid(array $attributes)
    {
        $validator = $this->makeValidator($attributes);

        if ($validator->fails()) {
            unlink($attributes['avatar']->avatarPath);
            throw new ValidationException($validator);
        }
    }
}
