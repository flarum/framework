<?php

namespace Flarum\Mail;

use Illuminate\Mail\Transport\LogTransport;
use Psr\Log\LoggerInterface;
use Swift_Mime_SimpleMessage;
use Swift_Mime_SimpleMimeEntity;

class FlarumLogTransport extends LogTransport
{
    /**
     * {@inheritdoc}
     *
     * @return int
     */
    public function send(Swift_Mime_SimpleMessage $message, &$failedRecipients = null)
    {
        $this->beforeSendPerformed($message);

        // Overriden to use info, so the log driver works in non-debug mode.
        $this->logger->info($this->getMimeEntityString($message));

        $this->sendPerformed($message);

        return $this->numberOfRecipients($message);
    }
}
