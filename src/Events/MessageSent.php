<?php

namespace Joemunapo\Whatsapp\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Joemunapo\Whatsapp\Message;

class MessageSent
{
    use Dispatchable, SerializesModels;

    public $content;

    public $messageId;

    public $message;

    public function __construct(Message $message, $content, $messageId)
    {
        $this->message = $message;
        $this->content = $content;
        $this->messageId = $messageId;
    }
}
