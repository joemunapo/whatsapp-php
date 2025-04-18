# Session Management

This package includes a powerful session management system that allows you to maintain conversation state with users across multiple messages.

## How Sessions Work

A session is created for each unique user (phone number) and stores data that persists between messages. This enables creating conversational flows and multi-step processes.

Sessions are stored in Laravel's cache system with a default TTL of 15 days.

## Basic Session Usage

The session functionality is built into the `Message` class, which is what you receive when handling a webhook:

```php
$message = Whatsapp::handleWebhook($payload);

if ($message) {
    // Store data in the session
    $message->remember('last_action', 'order_inquiry');
    $message->remember('product_id', 'ABC123');
    
    // Retrieve data from the session
    $lastAction = $message->get('last_action');
    
    // Remove data from the session
    $message->forget('temporary_data');
    
    // Clear all session data
    $message->forget('all');
}
```

### Storing Multiple Values

You can store multiple values at once:

```php
$message->remember([
    'order_id' => 'ORD-12345',
    'product_count' => 3,
    'shipping_method' => 'express'
]);
```

## Conversation Flow Management

One of the most powerful features of the session system is the ability to manage conversation flows:

```php
namespace App\Http\Controllers;

use Joemunapo\Whatsapp\Message;

class OrderController
{
    public function startOrderProcess(Message $message)
    {
        $message->reply("What product would you like to order?");
        
        // Set the next step in the conversation
        $message->setNext('receiveProductName', OrderController::class);
    }
    
    public function receiveProductName(Message $message)
    {
        // Store the product name from the user's message
        $message->remember('product_name', $message->text);
        
        // Ask for quantity
        $message->reply("How many units would you like to order?");
        
        // Set the next step
        $message->setNext('receiveQuantity', OrderController::class);
    }
    
    public function receiveQuantity(Message $message)
    {
        // Store the quantity
        $message->remember('quantity', $message->text);
        
        // Get the product name from the session
        $productName = $message->get('product_name');
        
        // Ask for confirmation
        $message->reply([
            'text' => "Please confirm your order: {$message->get('quantity')} units of {$productName}",
            'buttons' => ['Confirm', 'Cancel']
        ]);
        
        // Set the next step
        $message->setNext('confirmOrder', OrderController::class);
    }
    
    public function confirmOrder(Message $message)
    {
        if (strtolower($message->text) === 'confirm') {
            // Process the order
            $orderId = $this->createOrder(
                $message->get('product_name'),
                $message->get('quantity')
            );
            
            $message->reply("Thank you! Your order #{$orderId} has been placed.");
            
            // Clear the conversation state
            $message->forget('all');
        } else {
            $message->reply("Your order has been cancelled.");
            $message->forget('all');
        }
    }
    
    protected function createOrder($product, $quantity)
    {
        // Your order creation logic here
        return 'ORD-' . rand(10000, 99999);
    }
}
```

### Continuing the Conversation

In your webhook controller, check if the conversation has a next step:

```php
public function processMessage(Message $message)
{
    if ($message->hasNext()) {
        // Continue the existing conversation flow
        return $message->next();
    }
    
    // Otherwise, handle as a new message
    if ($message->isHi()) {
        // Start a new conversation
        return app(OrderController::class)->startOrderProcess($message);
    }
    
    // Default response for messages without context
    $message->reply("Hello! How can I help you today?");
}
```

## Best Practices

1. **Clear Sessions**: Always clear the session when a conversation ends to free up resources
2. **Error Handling**: Implement proper error handling in your conversation flow
3. **Timeouts**: Consider implementing timeouts for inactive conversations
4. **Data Validation**: Validate user input before storing it in the session
5. **Security**: Don't store sensitive information in sessions unless necessary