# Events System

This package includes an events system that allows you to listen for and respond to important actions, such as when messages are sent or received.

## Available Events

### ApiWhatsappSend

Dispatched when a raw message is sent directly to the WhatsApp API.

```php
namespace Joemunapo\Whatsapp\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ApiWhatsappSend
{
    use Dispatchable, SerializesModels;

    public function __construct(public $to, public $content, public $messageId) {}
}
```

### MessageSent

Dispatched when a message is sent using the package's higher-level methods.

```php
namespace Joemunapo\Whatsapp\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Joemunapo\Whatsapp\Message;

class MessageSent
{
    use Dispatchable, SerializesModels;

    public function __construct(public Message $message, public $content, public $messageId) {}
}
```

### MessageReceived

Dispatched when a message is received through the webhook.

```php
namespace Joemunapo\Whatsapp\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Joemunapo\Whatsapp\Message;

class MessageReceived
{
    use Dispatchable, SerializesModels;

    public function __construct(public Message $message) {}
}
```

## Creating Event Listeners

You can create listeners for these events using Laravel's event system. 

### Register the Events in Your EventServiceProvider

```php
protected $listen = [
    \Joemunapo\Whatsapp\Events\MessageReceived::class => [
        \App\Listeners\LogWhatsAppMessage::class,
        \App\Listeners\NotifyAdmins::class,
    ],
    \Joemunapo\Whatsapp\Events\MessageSent::class => [
        \App\Listeners\LogSentMessage::class,
        \App\Listeners\UpdateMessageStatus::class,
    ],
    \Joemunapo\Whatsapp\Events\ApiWhatsappSend::class => [
        \App\Listeners\TrackApiUsage::class,
    ],
];
```

### Create the Listeners

```php
namespace App\Listeners;

use Joemunapo\Whatsapp\Events\MessageReceived;
use Illuminate\Support\Facades\Log;

class LogWhatsAppMessage
{
    public function handle(MessageReceived $event)
    {
        $message = $event->message;
        
        Log::channel('whatsapp')->info('Message received', [
            'from' => $message->from,
            'message_id' => $message->id,
            'type' => $message->type,
            'text' => $message->text,
            'account' => $message->getAccount()->id ?? null,
        ]);
    }
}
```

```php
namespace App\Listeners;

use Joemunapo\Whatsapp\Events\MessageSent;
use App\Models\WhatsAppMessageLog;

class LogSentMessage
{
    public function handle(MessageSent $event)
    {
        WhatsAppMessageLog::create([
            'message_id' => $event->messageId,
            'to' => $event->message->from,
            'content' => json_encode($event->content),
            'account_id' => $event->message->getAccount()->id ?? null,
            'status' => 'sent',
        ]);
    }
}
```

## Event Use Cases

### Analytics and Tracking

```php
class TrackConversationMetrics
{
    public function handle(MessageReceived $event)
    {
        $message = $event->message;
        $account = $message->getAccount();
        
        // Increment message counts
        Metrics::increment('whatsapp.messages.received');
        Metrics::increment("whatsapp.account.{$account->id}.received");
        
        // Track message types
        Metrics::increment("whatsapp.type.{$message->type}");
        
        // Track conversation flow
        if ($message->isButton) {
            Metrics::increment('whatsapp.interaction.button');
        }
    }
}
```

### Integrations with Other Systems

```php
class SyncWithCRM
{
    protected $crmService;
    
    public function __construct(CRMService $crmService)
    {
        $this->crmService = $crmService;
    }
    
    public function handle(MessageReceived $event)
    {
        $message = $event->message;
        
        // Add message to CRM customer history
        $this->crmService->addCustomerInteraction(
            $message->from,
            'whatsapp_message',
            $message->text,
            ['message_id' => $message->id]
        );
    }
}
```

### Notifications

```php
class NotifyOfImportantMessages
{
    public function handle(MessageReceived $event)
    {
        $message = $event->message;
        
        // Check for important keywords
        if (preg_match('/(urgent|emergency|problem|issue)/i', $message->text)) {
            // Notify customer support team
            SupportTeam::notify(new UrgentWhatsAppMessage($message));
        }
    }
}
```

## Creating Custom Events

You can create your own custom events that extend the package's functionality:

```php
namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Joemunapo\Whatsapp\Message;

class WhatsAppOrderPlaced
{
    use Dispatchable, SerializesModels;
    
    public function __construct(
        public Message $message,
        public string $orderId,
        public array $orderDetails
    ) {}
}
```

Then dispatch it when appropriate:

```php
event(new WhatsAppOrderPlaced($message, $orderId, $orderDetails));
```