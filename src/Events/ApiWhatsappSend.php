<?php

namespace Joemunapo\Whatsapp\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ApiWhatsappSend
{
    use Dispatchable, SerializesModels;

    public function __construct(public $to, public $content, public $messageId) {}
}
