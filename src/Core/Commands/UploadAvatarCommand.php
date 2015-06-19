<?php namespace Flarum\Core\Commands;

use Psr\Http\Message\UploadedFileInterface;
use RuntimeException;

class UploadAvatarCommand
{
    public $userId;

    /**
     * @var \Psr\Http\Message\UploadedFileInterface
     */
    public $file;

    public $actor;

    public function __construct($userId, UploadedFileInterface $file, $actor)
    {
        if (empty($userId) || !intval($userId)) {
            throw new RuntimeException('No valid user ID specified.');
        }

        if (is_null($file)) {
            throw new RuntimeException('No file to upload');
        }

        $this->userId = $userId;
        $this->file = $file;
        $this->actor = $actor;
    }
}
