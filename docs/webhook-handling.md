# Webhook Handling

Webhooks allow your application to receive real-time notifications when users send messages to your WhatsApp Business account.

## Setting Up Webhooks

1. In your Meta Developer dashboard, go to your WhatsApp app settings
2. Configure the Webhook URL to point to your endpoint
3. Select the events you want to subscribe to (typically `messages`)
4. Set up a verification token for security

## Creating a Webhook Controller

In your Laravel application, create a controller to handle WhatsApp webhooks:

```php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Joemunapo\Whatsapp\Facades\Whatsapp;
use Illuminate\Support\Facades\Log;

class WhatsAppWebhookController extends Controller
{
    public function verify(Request $request)
    {
        // Handle the verification challenge from WhatsApp
        if ($request->has('hub_mode') && $request->input('hub_mode') === 'subscribe') {
            $verifyToken = config('services.whatsapp.verify_token');
            
            if ($request->input('hub_verify_token') === $verifyToken) {
                return response($request->input('hub_challenge'));
            }
        }
        
        return response('Verification failed', 403);
    }
    
    public function handle(Request $request)
    {
        // Parse the webhook payload
        $payload = $request->all();
        
        // Log the webhook for debugging (optional)
        Log::channel('whatsapp')->info('WhatsApp webhook received', $payload);
        
        // Process the message using the package
        $message = Whatsapp::handleWebhook($payload);
        
        if ($message) {
            // Message was successfully parsed
            $this->processMessage($message);
        }
        
        // Always return a 200 OK to acknowledge receipt
        return response()->json(['success' => true]);
    }
    
    protected function processMessage($message)
    {
        // Example: Respond based on message content
        try {
            if ($message->isHi()) {
                // Send a welcome message
                $message->reply('Hello! How can I assist you today?');
            } elseif ($message->isMedia()) {
                // Handle media messages
                $message->reply('Thank you for sending media. We\'ll process it soon.');
                
                // Process the media (optional)
                $mediaInfo = $message->getMediaContent();
                // ... store or process the media
            } else {
                // Forward to your business logic
                $this->routeMessage($message);
            }
        } catch (\Exception $e) {
            Log::error('Error processing WhatsApp message: ' . $e->getMessage());
            $message->reply('Sorry, we encountered an error processing your message.');
        }
    }
    
    protected function routeMessage($message)
    {
        // Implement your routing logic based on user state, message content, etc.
        // For example, you might use the Session features to maintain conversation state
        
        if ($message->hasNext()) {
            // Continue an existing conversation flow
            return $message->next();
        }
        
        // Route based on message content
        if (preg_match('/order/i', $message->text)) {
            return $this->handleOrderQuery($message);
        } elseif (preg_match('/support/i', $message->text)) {
            return $this->handleSupportRequest($message);
        }
        
        // Default response
        $message->reply([
            'text' => 'How can I help you today?',
            'buttons' => ['Place Order', 'Support', 'Information']
        ]);
    }
    
    // Add additional methods to handle specific scenarios
    protected function handleOrderQuery($message)
    {
        // ...
    }
    
    protected function handleSupportRequest($message)
    {
        // ...
    }
}
```

## Registering the Webhook Routes

Add routes for your webhook controller in `routes/api.php`:

```php
Route::get('webhooks/whatsapp', [WhatsAppWebhookController::class, 'verify']);
Route::post('webhooks/whatsapp', [WhatsAppWebhookController::class, 'handle']);
```

## Message Properties

When a message is received, the `handleWebhook` method returns a `Message` object with these properties:

- `from`: Sender's phone number
- `id`: Message ID
- `type`: Message type (text, image, video, etc.)
- `text`: Message text or caption
- `mediaId`: ID of attached media (if applicable)
- `contextId`: ID of message being replied to (if applicable)
- `isButton`: Whether this is a button response
- `isOrder`: Whether this is an order
- `user`: Can be used to store user information
- `whatsapp`: The WhatsApp instance used to handle this message

## Handling Different Message Types

The `Message` class provides helper methods to identify message types:

```php
if ($message->isText()) {
    // Text message
} elseif ($message->isMedia()) {
    // Media message (image, video, audio, document)
} elseif ($message->isLocation()) {
    // Location message
} elseif ($message->isContact()) {
    // Contact message
} elseif ($message->isButton) {
    // Button response
}
```

## Security Considerations

1. **Verify Webhook Sources**: Always validate that requests come from WhatsApp/Meta
2. **Use HTTPS**: Ensure your webhook endpoint uses HTTPS
3. **Validation**: Validate all incoming data before processing
4. **Rate Limiting**: Implement rate limiting to prevent abuse
5. **Error Handling**: Properly handle errors without exposing sensitive information