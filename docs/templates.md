# Message Templates

WhatsApp requires businesses to use pre-approved message templates when initiating conversations with users. Templates help ensure quality and prevent spam.

## What are Templates?

Templates are pre-approved message formats that can contain:
- Dynamic parameters (e.g., user names, order numbers)
- Media
- Buttons
- Pre-defined formats for common business scenarios

Templates must be created and approved in the Meta Business Manager before they can be used.

## Sending Template Messages

Once you have your approved templates, you can send them using the `sendTemplate` method:

```php
use Joemunapo\Whatsapp\Facades\Whatsapp;

$whatsapp = Whatsapp::useNumberId('your_whatsapp_number_id');

$phoneNumber = '1234567890';
$templateName = 'order_confirmation';
$languageCode = 'en_US';
$components = [];

$messageId = $whatsapp->sendTemplate($phoneNumber, $templateName, $languageCode, $components);
```

### Template Components

The `$components` parameter allows you to populate dynamic parts of your template:

```php
$components = [
    [
        'type' => 'header',
        'parameters' => [
            [
                'type' => 'image',
                'image' => [
                    'link' => 'https://example.com/logo.jpg'
                ]
            ]
        ]
    ],
    [
        'type' => 'body',
        'parameters' => [
            [
                'type' => 'text',
                'text' => 'John Doe'
            ],
            [
                'type' => 'text',
                'text' => 'ABC123'
            ],
            [
                'type' => 'text',
                'text' => '25 June 2023'
            ]
        ]
    ],
    [
        'type' => 'button',
        'sub_type' => 'url',
        'index' => 0,
        'parameters' => [
            [
                'type' => 'text',
                'text' => 'ABC123'
            ]
        ]
    ]
];
```

### When Replying to a Message

When handling a webhook, you can use the `replyWithTemplate` method:

```php
$message = Whatsapp::handleWebhook($payload);

if ($message) {
    $message->replyWithTemplate('order_confirmation', 'en_US', $components);
}
```

## Common Template Types

1. **Text Only**
   ```
   Hello {{1}}! Your order {{2}} has been confirmed and will be delivered on {{3}}.
   ```

2. **With Header Image**
   ```
   Header: [Image]
   Body: Hello {{1}}! Your order {{2}} has been confirmed and will be delivered on {{3}}.
   ```

3. **With Buttons**
   ```
   Header: Order Confirmation
   Body: Hello {{1}}! Your order {{2}} has been confirmed and will be delivered on {{3}}.
   Button 1: Track Order
   Button 2: Contact Support
   ```

## Best Practices

1. **Template Approval**:
   - Keep templates simple and clear
   - Avoid promotional content in first-time messages
   - Follow WhatsApp's guidelines to improve approval chances

2. **Dynamic Parameters**:
   - Number your parameters carefully ({{1}}, {{2}}, etc.)
   - Match parameters exactly to your approved template
   - Don't exceed the approved number of parameters

3. **Language Support**:
   - Create templates in all languages your audience uses
   - Use the correct language code (e.g., en_US, es_ES)

4. **Testing**:
   - Test templates with all possible parameter variations
   - Verify that all buttons work correctly