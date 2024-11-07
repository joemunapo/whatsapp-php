<?php

namespace Joemunapo\Whatsapp\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ApiWhatsappSend
{
    use Dispatchable, SerializesModels;

    public $content;

    public $messageId;

    public function __construct($content, $messageId)
    {
        $this->content = $content;
        $this->messageId = $messageId;
    }
}
