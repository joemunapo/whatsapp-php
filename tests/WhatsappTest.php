<?php

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Joemunapo\Whatsapp\AccountResolver;
use Joemunapo\Whatsapp\Message;
use Joemunapo\Whatsapp\Whatsapp;

// tests/WhatsappTest.php

// Set up configuration before each test
beforeEach(function () {
    Config::set('whatsapp.fields', [
        'token' => 'whatsapp_token',
        'number_id' => 'number_id',
        'catalog_id' => 'catalog_id',
    ]);

    Config::set('whatsapp.account_model', \stdClass::class);
});

// Test handling an incoming webhook
it('can handle incoming webhooks', function () {
    $numberId = '1234567890';
    $from = '0987654321';
    $message = 'Hello, World!';
    $incomingData = [
        'entry' => [
            [
                'id' => 'unique_message_id',
                'messaging_product' => 'whatsapp',
                'timestamp' => 1643723400,
                'changes' => [
                    [
                        'value' => [
                            'messaging_product' => 'whatsapp',
                            'metadata' => [
                                'display_phone_number' => '+1234567890',
                                'phone_number_id' => $numberId,
                            ],
                            'messages' => [
                                [
                                    'from' => $from,
                                    'to' => 'recipient_phone_number',
                                    'id' => 'message_id',
                                    'timestamp' => 1643723400,
                                    'text' => [
                                        'body' => $message,
                                    ],
                                    'type' => 'text',
                                ],
                            ],
                        ],
                        'field' => 'messages',
                    ],
                ],
            ],
        ],
    ];

    // Mock the AccountResolver to return a test account
    $mockResolver = Mockery::mock(AccountResolver::class);
    $mockResolver->shouldReceive('resolve')->andReturn([
        'token' => 'test_token',
        'number_id' => $numberId,
        'catalog_id' => 'test_catalog_id',
    ]);

    // Ensure that the static instance uses the mocked resolver
    Whatsapp::getInstance($mockResolver);

    // Call the handleWebhook method directly
    $response = Whatsapp::handleWebhook($incomingData);

    // Assert the message was handled correctly
    expect($response)->toBeInstanceOf(Message::class);
    expect($response->from)->toBe($from);
    expect($response->text)->toBe($message);
});

// Test sending a message
it('can send a text message', function () {
    $numberId = '1234567890';
    $recipient = '0987654321';
    $message = 'Hello, World!';

    // Mock the HTTP request to the WhatsApp API
    Http::fake([
        "https://graph.facebook.com/v18.0/{$numberId}/messages" => Http::response(['messages' => []], 200),
    ]);

    // Mock the AccountResolver to return a test account
    $mockResolver = Mockery::mock(AccountResolver::class);
    $mockResolver->shouldReceive('resolve')->andReturn([
        'token' => 'test_token',
        'number_id' => $numberId,
        'catalog_id' => 'test_catalog_id',
    ]);

    // Ensure that the static instance uses the mocked resolver
    Whatsapp::getInstance($mockResolver);

    // Send the message using the WhatsApp facade
    $response = Whatsapp::useNumberId($numberId)->sendMessage($recipient, $message);

    // Assert the request was made to the correct endpoint
    Http::assertSent(function ($request) use ($numberId, $recipient, $message) {
        return $request->url() === "https://graph.facebook.com/v18.0/{$numberId}/messages" &&
            $request['recipient_type'] === 'individual' &&
            $request['to'] === $recipient &&
            $request['type'] === 'text' &&
            $request['text']['body'] === $message;
    });

    // Assert the response
    expect($response)->toBe(['messages' => []]);
});

it('can send a media message', function () {
    $numberId = '1234567890';
    $recipient = '0987654321';
    $mediaType = 'image';
    $mediaUrl = 'https://example.com/image.jpg';
    $caption = 'Check out this image!';

    // Mock the HTTP request to the WhatsApp API
    Http::fake([
        "https://graph.facebook.com/v18.0/{$numberId}/messages" => Http::response(['messages' => [['id' => 'media_message_id']]], 200),
    ]);

    // Mock the AccountResolver to return a test account
    $mockResolver = Mockery::mock(AccountResolver::class);
    $mockResolver->shouldReceive('resolve')->andReturn([
        'token' => 'test_token',
        'number_id' => $numberId,
        'catalog_id' => 'test_catalog_id',
    ]);

    // Ensure that the static instance uses the mocked resolver
    Whatsapp::getInstance($mockResolver);

    // Send the media message using the WhatsApp facade
    $response = Whatsapp::useNumberId($numberId)->sendMedia($recipient, $mediaType, $mediaUrl, $caption);

    // Assert the request was made to the correct endpoint with the correct data
    Http::assertSent(function ($request) use ($numberId, $recipient, $mediaType, $mediaUrl, $caption) {
        return $request->url() === "https://graph.facebook.com/v18.0/{$numberId}/messages" &&
            $request['messaging_product'] === 'whatsapp' &&
            $request['recipient_type'] === 'individual' &&
            $request['to'] === $recipient &&
            $request['type'] === $mediaType &&
            $request[$mediaType]['link'] === $mediaUrl &&
            $request[$mediaType]['caption'] === $caption;
    });

    // Assert the response
    expect($response)->toBe(['messages' => [['id' => 'media_message_id']]]);
});

it('throws an exception when account is not found', function () {
    $nonExistentNumberId = '9999999999';

    // Mock the AccountResolver to return null (simulating account not found)
    $mockResolver = Mockery::mock(AccountResolver::class);
    $mockResolver->shouldReceive('resolve')->with($nonExistentNumberId)->andReturn(null);

    // Ensure that the static instance uses the mocked resolver
    Whatsapp::getInstance($mockResolver);

    // Attempt to use a non-existent number ID
    Whatsapp::useNumberId($nonExistentNumberId)->sendMessage('1234567890', 'Test message');
})->throws(Exception::class, 'No WhatsApp account found for number ID: 9999999999');

// Clean up after each test
afterEach(function () {
    Mockery::close();
});
