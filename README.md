# WhatsApp Cloud API Integration for Laravel

[![Latest Version on Packagist](https://img.shields.io/packagist/v/joemunapo/whatsapp-php.svg?style=flat-square)](https://packagist.org/packages/joemunapo/whatsapp-php)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/joemunapo/whatsapp-php/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/joemunapo/whatsapp-php/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/joemunapo/whatsapp-php/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/joemunapo/whatsapp-php/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/joemunapo/whatsapp-php.svg?style=flat-square)](https://packagist.org/packages/joemunapo/whatsapp-php)

This package provides a seamless integration of the WhatsApp Cloud API for Laravel applications. It offers a flexible, database-driven approach to manage multiple WhatsApp business accounts within a single application.

## Key Features

- Easy integration with Laravel applications
- Support for multiple WhatsApp business accounts
- Database-driven account management
- Webhook handling for incoming messages
- Simple interface for sending text and media messages
- Template message support
- Marking messages as read
- Retrieving media content

Ideal for multi-tenant applications or businesses managing multiple WhatsApp accounts, this package simplifies the complexities of WhatsApp integration while maintaining the flexibility to fit into your existing database structure.

## Installation

You can install the package via composer:

```bash
composer require joemunapo/whatsapp-php
```

## Configuration

Publish the config file:

```bash
php artisan vendor:publish --tag="whatsapp-php-config" 
```

Update the published config file in `config/whatsapp.php`:

```php
return [
    'account_model' => \App\Models\Business::class,
    'fields' => [
        'number_id' => 'number_id',
        'token' => 'whatsapp_token',
        'catalog_id' => 'catalog_id',
    ],
];
```

Ensure your database model (e.g., `Business`) has the necessary fields to store WhatsApp account details.

## Usage

### Handling Webhooks

In your webhook handler:

```php
use Joemunapo\Whatsapp\Whatsapp;

public function handleWebhook(Request $request)
{
    $message = Whatsapp::handleWebhook($request->all());

    if ($message) {
        // Process the incoming message
        $message->markAsRead();
        $message->reply('Thank you for your message!');
    }

    return response()->json(['success' => true]);
}
```

### Sending Messages

To send a message, first select the WhatsApp account using the `useNumberId()` method, then call the appropriate send method:

```php
use Joemunapo\Whatsapp\Whatsapp;

// Send a text message
Whatsapp::useNumberId('your_number_id')
    ->sendMessage('recipient_phone_number', 'Hello, World!');

// Send a media message
Whatsapp::useNumberId('your_number_id')
    ->sendMedia('recipient_phone_number', 'image', 'https://example.com/image.jpg', 'Check out this image!');

// Send a template message
Whatsapp::useNumberId('your_number_id')
    ->sendTemplate('recipient_phone_number', 'template_name', 'en_US', [
        // Template components
    ]);
```

### Marking Message as Read

You can mark a message as read using the `markMessageAsRead()` method:

```php
Whatsapp::useNumberId('your_number_id')
    ->markMessageAsRead('recipient_phone_number', 'message_id');
```

### Retrieving Media Content

To retrieve media content, use the `getMedia()` method:

```php
$mediaContent = Whatsapp::useNumberId('your_number_id')
    ->getMedia('media_id');
```

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Joe Munapo](https://github.com/joemunapo)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
