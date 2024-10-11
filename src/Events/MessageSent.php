<?php

namespace Joemunapo\Whatsapp\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageSent
{
    use Dispatchable, SerializesModels;

    public $to;

    public $content;

    public $messageId;

    public function __construct($to, $content, $messageId)
    {
        $this->to = $to;
        $this->content = $content;
        $this->messageId = $messageId;
    }
}
