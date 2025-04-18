# Installation and Configuration

## Requirements

- PHP 8.2 or higher
- Laravel 10.0 or higher

## Installation

You can install the package via Composer:

```bash
composer require joemunapo/whatsapp-php
```

## Publishing the Configuration

After installation, you need to publish the configuration file:

```bash
php artisan vendor:publish --provider="Joemunapo\Whatsapp\WhatsappServiceProvider"
```

This will create a `whatsapp.php` configuration file in your `config` directory.

## Configuration

Open the published configuration file at `config/whatsapp.php` and update it with your settings:

```php
return [
    'account_model' => \App\Models\YourWhatsAppAccountModel::class,
    'fields' => [
        'number_id' => 'number_id',
        'token' => 'whatsapp_token',
        'catalog_id' => 'catalog_id',
    ],
];
```

### Account Model Setup

You need to create a model to store WhatsApp account details. For example:

```php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WhatsAppAccount extends Model
{
    protected $fillable = [
        'number_id',     // WhatsApp phone number ID from Facebook/Meta
        'whatsapp_token', // Access token for the WhatsApp API
        'catalog_id',    // Optional: For product catalogs
        // Add any other fields you need
    ];
}
```

And create a corresponding migration:

```php
Schema::create('whats_app_accounts', function (Blueprint $table) {
    $table->id();
    $table->string('number_id')->unique();
    $table->string('whatsapp_token');
    $table->string('catalog_id')->nullable();
    $table->timestamps();
});
```

### Meta Developer Setup

To use this package, you need:

1. A Meta Developer account
2. A WhatsApp Business Account
3. A phone number registered with WhatsApp Business API
4. An access token

Follow these steps:

1. Go to [Meta Developers](https://developers.facebook.com/) and create an app
2. Add the WhatsApp product to your app
3. Set up your WhatsApp business account and phone number
4. Generate a permanent access token
5. Note your phone number ID (this is different from the actual phone number)

Once you have these details, store them in your database using the model you configured above.