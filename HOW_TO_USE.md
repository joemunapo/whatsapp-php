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




# TOBE DELETED
You're absolutely right. I apologize for the oversight. To use the Facade pattern effectively and allow for static method calls, we need to modify our Whatsapp class and create a corresponding Facade. Let's update the structure:

1. First, let's modify the Whatsapp class to support static calls:

```php
<?php

namespace Joemunapo\Whatsapp;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Arr;
use Exception;

class Whatsapp
{
    protected static $instance;
    protected $token;
    protected $numberId;
    protected $catalogId;
    protected $apiUrl = 'https://graph.facebook.com/v16.0';
    protected $accountResolver;

    public function __construct(AccountResolver $accountResolver)
    {
        $this->accountResolver = $accountResolver;
    }

    public static function getInstance(AccountResolver $accountResolver)
    {
        if (!self::$instance) {
            self::$instance = new self($accountResolver);
        }
        return self::$instance;
    }

    public static function useNumberId($numberId)
    {
        $instance = self::getInstance(app(AccountResolver::class));
        $account = $instance->accountResolver->resolve($numberId);
        if (!$account) {
            throw new Exception("No WhatsApp account found for number ID: $numberId");
        }
        $instance->setAccount($account['token'], $account['number_id'], $account['catalog_id'] ?? null);
        return $instance;
    }

    protected function setAccount($token, $numberId, $catalogId = null)
    {
        $this->token = $token;
        $this->numberId = $numberId;
        $this->catalogId = $catalogId;
        return $this;
    }

    public function sendMessage($to, $content)
    {
        $this->validateSetup();

        $data = [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $to,
            'type' => 'text',
            'text' => ['body' => $content]
        ];

        return $this->sendRequest('messages', $data);
    }

    public function sendMedia($to, $mediaType, $mediaUrl, $caption = null)
    {
        $this->validateSetup();

        $data = [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $to,
            'type' => $mediaType,
            $mediaType => [
                'link' => $mediaUrl,
                'caption' => $caption
            ]
        ];

        return $this->sendRequest('messages', $data);
    }

    public function sendTemplate($to, $templateName, $languageCode, $components = [])
    {
        $this->validateSetup();

        $data = [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $to,
            'type' => 'template',
            'template' => [
                'name' => $templateName,
                'language' => ['code' => $languageCode],
                'components' => $components
            ]
        ];

        return $this->sendRequest('messages', $data);
    }

    public function markMessageAsRead($phoneNumber, $messageId)
    {
        $this->validateSetup();

        $data = [
            'messaging_product' => 'whatsapp',
            'status' => 'read',
            'message_id' => $messageId
        ];

        return $this->sendRequest('messages', $data);
    }

    public function getMedia($mediaId)
    {
        $this->validateSetup();

        $response = Http::withToken($this->token)->get("{$this->apiUrl}/{$mediaId}");

        if ($response->failed()) {
            throw new Exception("Failed to get media: " . $response->body());
        }

        return $response->json();
    }

    protected function sendRequest($endpoint, $data)
    {
        $url = "{$this->apiUrl}/{$this->numberId}/{$endpoint}";

        $response = Http::withToken($this->token)->post($url, $data);

        if ($response->failed()) {
            throw new Exception("WhatsApp API request failed: " . $response->body());
        }

        return $response->json();
    }

    protected function validateSetup()
    {
        if (!$this->token || !$this->numberId) {
            throw new Exception("WhatsApp account not properly configured. Use useNumberId() before making requests.");
        }
    }

    public static function handleWebhook($payload)
    {
        $instance = self::getInstance(app(AccountResolver::class));
        $entry = Arr::get($payload, 'entry.0', null);
        if (!$entry) {
            return null;
        }

        $change = Arr::get($entry, 'changes.0', null);
        if (!$change || Arr::get($change, 'field') !== 'messages') {
            return null;
        }

        $messageData = Arr::get($change, 'value.messages.0', null);
        if (!$messageData) {
            return null;
        }

        $numberId = Arr::get($change, 'value.metadata.phone_number_id');
        $instance->useNumberId($numberId);

        return new Message($messageData, $instance);
    }
}
```

2. Now, let's create a Facade for this class:

```php
<?php

namespace Joemunapo\Whatsapp\Facades;

use Illuminate\Support\Facades\Facade;

class Whatsapp extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'whatsapp';
    }
}
```

3. Update your service provider to bind the Whatsapp class:

```php
<?php

namespace Joemunapo\Whatsapp;

use Illuminate\Support\ServiceProvider;

class WhatsappServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton('whatsapp', function ($app) {
            return new Whatsapp($app->make(AccountResolver::class));
        });
    }

    // ... other methods
}
```

Now you can use the Whatsapp class statically in your application:

```php
use Joemunapo\Whatsapp\Facades\Whatsapp;

// In a controller or anywhere in your application
Whatsapp::useNumberId('your_number_id')->sendMessage('recipient_number', 'Hello, World!');

// Handling webhook
Route::post('/webhook/whatsapp', function (Request $request) {
    $message = Whatsapp::handleWebhook($request->all());
    if ($message) {
        // Process the message
    }
    return response()->json(['success' => true]);
});
```

This structure allows for static method calls while still maintaining the ability to use different WhatsApp accounts by calling `useNumberId()` before other methods. The Facade will handle the creation and management of the Whatsapp instance behind the scenes.

Remember to update your package documentation to reflect these changes and provide examples of how to use the static methods via the Facade.