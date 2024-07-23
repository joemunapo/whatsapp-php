# WhatsApp Cloud API Integration for Laravel

[![Latest Version on Packagist](https://img.shields.io/packagist/v/joemunapo/whatsapp-php.svg?style=flat-square)](https://packagist.org/packages/joemunapo/whatsapp-php)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/joemunapo/whatsapp-php/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/joemunapo/whatsapp-php/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/joemunapo/whatsapp-php/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/joemunapo/whatsapp-php/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/joemunapo/whatsapp-php.svg?style=flat-square)](https://packagist.org/packages/joemunapo/whatsapp-php)

This package provides a seamless integration of the WhatsApp Cloud API for Laravel applications. It offers a flexible, database-driven approach to manage multiple WhatsApp business accounts within a single application.

## Key Features

- Dynamic account resolution based on incoming webhooks
- Configurable database model and field mappings
- Support for sending messages, media, and interactive content
- Session management for stateful conversations
- Easy-to-use facade for quick implementation

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
use Joemunapo\Whatsapp\Facades\WhatsApp;

public function handleWebhook(Request $request)
{
    $numberId = $request->input('entry.0.changes.0.value.metadata.phone_number_id');
    
    try {
        $message = WhatsApp::useNumberId($numberId)->handleMessage($request->all());
        // Process the message...
    } catch (\Exception $e) {
        // Handle case where no account is found for the number ID
        logger()->error("WhatsApp account not found: " . $e->getMessage());
    }
}
```

### Sending Messages

```php
use Joemunapo\Whatsapp\Facades\WhatsApp;

try {
    WhatsApp::useNumberId('1234567890')->sendMessage('1234567890', 'Hello, World!');
} catch (\Exception $e) {
    // Handle error (e.g., account not found)
}
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