# Joe Munapo's WhatsApp PHP Package

[![Latest Version on Packagist](https://img.shields.io/packagist/v/joemunapo/whatsapp-php.svg?style=flat-square)](https://packagist.org/packages/joemunapo/whatsapp-php)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/joemunapo/whatsapp-php/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/joemunapo/whatsapp-php/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/joemunapo/whatsapp-php/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/joemunapo/whatsapp-php/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/joemunapo/whatsapp-php.svg?style=flat-square)](https://packagist.org/packages/joemunapo/whatsapp-php)

This package provides a simple and efficient way to integrate WhatsApp Cloud API functionality into your Laravel application.

## Installation

You can install the package via composer:

```bash
composer require joemunapo/whatsapp-php
```

## Configuration

After installation, publish the configuration file:

```bash
php artisan vendor:publish --provider="Joemunapo\Whatsapp\WhatsappServiceProvider"
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

### Initializing the WhatsApp instance

```php
use Joemunapo\Whatsapp\Whatsapp;

$whatsapp = Whatsapp::useNumberId('your_whatsapp_number_id');
```

### Sending a Text Message

```php
$to = '1234567890';
$content = (object) [
    'type' => 'text',
    'text' => [
        'body' => 'Hello, World!'
    ]
];

$messageId = $whatsapp->sendMessage($to, $content);
```

### Sending an Interactive Message (Buttons)

```php
$content = (object) [
    'type' => 'interactive',
    'text' => 'Please choose an option:',
    'buttons' => ['Option 1', 'Option 2', 'Option 3']
];

$whatsapp->sendMessage($to, $content);
```

### Sending Media

```php
$mediaType = 'image';
$mediaUrl = 'https://example.com/image.jpg';
$caption = 'Check out this image!';

$whatsapp->sendMedia($to, $mediaType, $mediaUrl, $caption);
```

### Sending a Template Message

```php
$templateName = 'hello_world';
$languageCode = 'en_US';
$components = [
    [
        'type' => 'body',
        'parameters' => [
            ['type' => 'text', 'text' => 'John Doe']
        ]
    ]
];

$whatsapp->sendTemplate($to, $templateName, $languageCode, $components);
```

### Handling Webhooks

```php
$payload = // ... webhook payload from WhatsApp
$message = Whatsapp::handleWebhook($payload);

if ($message) {
    // Process the message
    $message->reply('Thank you for your message!');
}
```

### Getting Media

When you receive a message with media (like an image, video, or document), you can retrieve the media content using the `getMedia` method:

```php
$mediaId = 'media_id_from_webhook_payload';
$mediaInfo = $whatsapp->getMedia($mediaId);

// The $mediaInfo will contain details about the media, including the URL to download it
$mediaUrl = $mediaInfo['url'];

// You can then download and process the media as needed
```

## Features

- Send text messages
- Send interactive messages (buttons, lists, product lists)
- Send media (images, videos, documents)
- Send template messages
- Handle incoming messages via webhooks
- Mark messages as read
- Retrieve media content

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
