# Account Management

This package is designed to work with multiple WhatsApp accounts, making it ideal for multi-tenant applications or businesses managing multiple phone numbers.

## Account Configuration

The package uses an `AccountResolver` to dynamically fetch account details based on the WhatsApp number ID. This allows you to store and manage multiple accounts in your database.

### Configuration File

In your `config/whatsapp.php` file:

```php
return [
    'account_model' => \App\Models\WhatsAppAccount::class,
    'fields' => [
        'number_id' => 'number_id',
        'token' => 'whatsapp_token',
        'catalog_id' => 'catalog_id',
    ],
];
```

### Account Model

Create a model to store your WhatsApp account details:

```php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WhatsAppAccount extends Model
{
    protected $fillable = [
        'name',             // Account name or identifier
        'number_id',        // WhatsApp phone number ID from Meta
        'whatsapp_token',   // API access token
        'catalog_id',       // Optional: For product catalogs
        'active',           // Optional: To enable/disable accounts
        // Add any other fields you need
    ];
    
    // Optional relationships
    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }
    
    public function templates()
    {
        return $this->hasMany(WhatsAppTemplate::class);
    }
}
```

### Migration Example

```php
Schema::create('whats_app_accounts', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->string('number_id')->unique();
    $table->text('whatsapp_token');
    $table->string('catalog_id')->nullable();
    $table->boolean('active')->default(true);
    $table->foreignId('tenant_id')->nullable()->constrained();
    $table->timestamps();
});
```

## Using Different Accounts

To use a specific WhatsApp account, use the `useNumberId` method:

```php
use Joemunapo\Whatsapp\Facades\Whatsapp;

// Using the specific number ID
$whatsapp = Whatsapp::useNumberId('1234567890');

// Now you can send messages with this account
$whatsapp->sendMessage('recipient_number', $content);
```

## Handling Webhooks with Multiple Accounts

When processing webhooks, the package automatically identifies which account the message belongs to:

```php
public function handle(Request $request)
{
    $payload = $request->all();
    
    // The package automatically resolves the correct account based on the payload
    $message = Whatsapp::handleWebhook($payload);
    
    if ($message) {
        // The message object is already associated with the correct account
        $account = $message->getAccount();
        
        // You can use account information for business logic
        if ($account->tenant_id === 5) {
            // Handle tenant-specific logic
        }
        
        // Reply using the same account automatically
        $message->reply('Thank you for your message!');
    }
    
    return response()->json(['success' => true]);
}
```

## Custom Account Resolver

If you need custom account resolution logic, you can extend the `AccountResolver` class:

```php
namespace App\Services\WhatsApp;

use Joemunapo\Whatsapp\AccountResolver as BaseResolver;

class CustomAccountResolver extends BaseResolver
{
    public function resolve($numberId): ?array
    {
        // Add custom logic, like filtering by active status
        $this->account = $this->model::where($this->fields['number_id'], $numberId)
            ->where('active', true)
            ->first();
            
        if (!$this->account) {
            return null;
        }
        
        // You can add additional fields here if needed
        return [
            'token' => $this->account->{$this->fields['token']},
            'number_id' => $this->account->{$this->fields['number_id']},
            'catalog_id' => $this->account->{$this->fields['catalog_id']},
            'tenant_id' => $this->account->tenant_id,
        ];
    }
}
```

Register your custom resolver in a service provider:

```php
public function register()
{
    $this->app->bind(
        \Joemunapo\Whatsapp\AccountResolver::class,
        \App\Services\WhatsApp\CustomAccountResolver::class
    );
}
```

## Security Considerations

1. **Token Storage**: Store WhatsApp tokens securely (consider encrypting them)
2. **Access Control**: Implement proper access controls for account management
3. **Logging**: Log all account usage for audit purposes
4. **Monitoring**: Monitor API limits across all accounts
5. **Token Rotation**: Implement a process for rotating tokens periodically