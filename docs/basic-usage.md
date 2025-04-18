# Basic Usage

## Initializing the WhatsApp Instance

Before sending any messages, you need to initialize the WhatsApp instance with a specific number ID:

```php
use Joemunapo\Whatsapp\Facades\Whatsapp;

// Using the facade
$whatsapp = Whatsapp::useNumberId('your_whatsapp_number_id');

// Or using dependency injection
public function sendMessage(Whatsapp $whatsappService)
{
    $whatsapp = $whatsappService->setNumberId('your_whatsapp_number_id');
    // ...
}
```

The number ID corresponds to the WhatsApp phone number ID from your Meta Developer account, not the actual phone number.

## Sending a Simple Text Message

The most basic way to send a message is a text message:

```php
$to = '1234567890'; // Recipient's phone number with country code, no '+' or spaces
$content = (object) [
    'type' => 'text',
    'text' => [
        'body' => 'Hello from WhatsApp PHP!'
    ]
];

$messageId = $whatsapp->sendMessage($to, $content);
```

The `sendMessage` method returns the message ID if successful, which you can use to track the message.

## Simplified Text Message

You can also use a simpler syntax for text messages when using the reply method on a received message:

```php
// When handling a webhook
$message = Whatsapp::handleWebhook($payload);

if ($message) {
    // Simple text reply
    $message->reply("Thank you for your message!");
    
    // With formatting
    $message->reply([
        'header' => 'Welcome',
        'text' => 'Thank you for contacting us.',
        'caption' => 'Customer Support'
    ]);
}
```

## Marking Messages as Read

To mark a message as read:

```php
// Manually
$whatsapp->markMessageAsRead($phoneNumber, $messageId);

// When handling a webhook (automatically marks as read)
$message = Whatsapp::handleWebhook($payload);
if ($message) {
    $message->reply("Thanks for your message!");
    // The reply method automatically marks the message as read
}
```

## Error Handling

The package throws exceptions when errors occur. It's recommended to wrap API calls in try-catch blocks:

```php
try {
    $messageId = $whatsapp->sendMessage($to, $content);
    // Message sent successfully
} catch (\Exception $e) {
    // Handle the error
    Log::error('WhatsApp error: ' . $e->getMessage());
}
```

## Events

When sending or receiving messages, the package dispatches events that you can listen to:

- `ApiWhatsappSend` - Dispatched when a raw message is sent via the API
- `MessageSent` - Dispatched when a message is sent via the package
- `MessageReceived` - Dispatched when a message is received via webhook

You can create listeners for these events to perform additional actions.