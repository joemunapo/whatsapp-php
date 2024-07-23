# To use your WhatsApp package in your Laravel project, follow these steps

1. Install the package:
If your package is on Packagist, you can install it via Composer:

```sh
composer require joemunapo/whatsapp
```

If it's not on Packagist yet, you can add it to your composer.json file as a local path repository.

2. Publish the configuration:
Run the following command to publish the package's configuration file:

```sh
php artisan vendor:publish --provider="Joemunapo\Whatsapp\WhatsappServiceProvider" --tag="config"
```

3. Configure your WhatsApp accounts:
In your .env file, add the necessary configuration for your WhatsApp account model:

```sh
WHATSAPP_ACCOUNT_MODEL=App\Models\Business
WHATSAPP_FIELD_NUMBER_ID=number_id
WHATSAPP_FIELD_TOKEN=whatsapp_token
WHATSAPP_FIELD_CATALOG_ID=catalog_id
```

Make sure your Business model (or whatever model you're using) has these fields.

4. Set up a route for the WhatsApp webhook:
In your `routes/api.php` file, add:

```php
use Joemunapo\Whatsapp\Facades\Whatsapp;

Route::post('/webhook/whatsapp', function (Request $request) {
    $whatsapp = Whatsapp::handleWebhook($request->all());
    if ($whatsapp) {
        // Process the message
        // For example:
        if ($whatsapp->isText()) {
            $whatsapp->reply("You said: " . $whatsapp->text);
        }
    }
    return response()->json(['success' => true]);
});
```

5. Using the WhatsApp facade in your controllers or other parts of your application:

```php
use Joemunapo\Whatsapp\Facades\Whatsapp;

class WhatsAppController extends Controller
{
    public function sendMessage($to, $message)
    {
        try {
            $result = Whatsapp::useNumberId('your_whatsapp_number_id')
                ->sendMessage($to, $message);
            
            return response()->json(['success' => true, 'result' => $result]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function sendMedia($to, $mediaType, $mediaUrl, $caption)
    {
        try {
            $result = Whatsapp::useNumberId('your_whatsapp_number_id')
                ->sendMedia($to, $mediaType, $mediaUrl, $caption);
            
            return response()->json(['success' => true, 'result' => $result]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
}
```

6. If you're using multiple WhatsApp accounts, you can switch between them using the `useNumberId` method:

```php
Whatsapp::useNumberId('number_id_1')->sendMessage($to, $message);
Whatsapp::useNumberId('number_id_2')->sendMessage($to, $message);
```

7. Remember to set up proper error handling and logging in your application to catch and report any issues with the WhatsApp API.

8. For the webhook to work, you'll need to configure your WhatsApp Business API to send webhooks to your application's URL. Make sure your server is accessible and the webhook URL is properly set in the WhatsApp Business API settings.

9. You may want to add middleware for webhook verification if required by the WhatsApp API.

By following these steps, you should be able to integrate and use your WhatsApp package in your Laravel project. Make sure to refer to your package's documentation for any specific usage instructions or additional features you've implemented.
