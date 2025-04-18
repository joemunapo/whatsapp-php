# Interactive Messages

WhatsApp Cloud API supports various interactive message types like buttons, lists, and product catalogs. This package makes it easy to create and send these interactive messages.

## Button Messages

Button messages display up to 3 buttons that users can tap to respond:

```php
use Joemunapo\Whatsapp\Facades\Whatsapp;

$whatsapp = Whatsapp::useNumberId('your_whatsapp_number_id');

$content = (object) [
    'text' => 'Please choose an option:',
    'buttons' => ['Yes', 'No', 'Maybe']
];

$whatsapp->sendMessage($phoneNumber, $content);
```

For more control over button IDs:

```php
$content = (object) [
    'text' => 'Please choose an option:',
    'buttons' => [
        ['id' => 'btn_yes', 'title' => 'Yes'],
        ['id' => 'btn_no', 'title' => 'No'],
        ['id' => 'btn_maybe', 'title' => 'Maybe']
    ]
];
```

## List Messages

List messages allow you to present multiple options in a scrollable list:

```php
$content = (object) [
    'text' => 'Please select a color:',
    'list_button_title' => 'View Colors',
    'list_title' => 'Available Colors',
    'list' => ['Red', 'Green', 'Blue', 'Yellow', 'Purple']
];

$whatsapp->sendMessage($phoneNumber, $content);
```

For lists with descriptions:

```php
$content = (object) [
    'text' => 'Please select a product:',
    'list_button_title' => 'View Products',
    'list_title' => 'Our Products',
    'description_list' => [
        (object) [
            'id' => 'prod_1',
            'title' => 'Laptop',
            'description' => 'High-performance laptop'
        ],
        (object) [
            'id' => 'prod_2',
            'title' => 'Smartphone',
            'description' => 'Latest model with great camera'
        ]
    ]
];
```

## Product List Messages

If you have a WhatsApp catalog, you can send product lists:

```php
// Make sure catalog_id is set in your WhatsApp account
$content = (object) [
    'text' => 'Check out these products:',
    'results_title' => 'Featured Products',
    'results' => ['product_id_1', 'product_id_2', 'product_id_3'],
    'related_title' => 'You might also like',
    'related' => ['product_id_4', 'product_id_5']
];

// When handling a webhook
$message->replyWithProducts($content);

// Or using the WhatsApp instance directly
$whatsapp->sendMessage($phoneNumber, $content);
```

## Flow Messages

WhatsApp Flows are complex interactive experiences:

```php
$content = (object) [
    'text' => 'Complete your purchase',
    'flow' => [
        'id' => 'flow_xyz',
        'token' => 'your_flow_token',
        'cta' => 'Start Shopping',
        'action' => 'navigate',
        'screen' => 'welcome',
        'mode' => 'published',
        'data' => [
            'customer_id' => '12345',
            'item_id' => 'abc123'
        ]
    ]
];

$whatsapp->sendMessage($phoneNumber, $content);
```

## Adding Headers and Footers

You can add headers and footers to interactive messages:

```php
$content = (object) [
    'header' => 'Special Offer',
    'text' => 'Would you like to receive our newsletter?',
    'caption' => 'Reply to subscribe',
    'buttons' => ['Subscribe', 'Not Now']
];
```

## Handling Responses

When a user interacts with these messages, you'll receive a webhook payload. Use the `handleWebhook` method to process these interactions:

```php
$message = Whatsapp::handleWebhook($payload);

if ($message) {
    if ($message->isButton) {
        // This is a button response
        $buttonId = $message->text;
        
        if ($buttonId === 'Subscribe' || $buttonId === 'btn_yes') {
            // User clicked the Subscribe/Yes button
            $message->reply('Thank you for subscribing!');
        }
    }
}
```