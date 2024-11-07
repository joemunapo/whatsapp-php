<?php

// app/Events/MessageReceived.php

namespace Joemunapo\Whatsapp\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Joemunapo\Whatsapp\Message;

class MessageReceived
{
    use Dispatchable, SerializesModels;

    public function __construct(public Message $message) {}
}
