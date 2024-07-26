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

// Clean up after each test
afterEach(function () {
    Mockery::close();
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

    $whatsapp = Whatsapp::getInstance($mockResolver);
    $response = Whatsapp::handleWebhook($incomingData, $whatsapp);

    // Assert the message was handled correctly
    expect($response)->toBeInstanceOf(Message::class);
    expect($response->from)->toBe($from);
    expect($response->text)->toBe($message);
});

it('can send a text message', function () {
    $numberId = '1234567890';
    $recipient = '0987654321';
    $message = 'Hello, World!';

    // Mock the HTTP request to the WhatsApp API
    Http::fake([
        "https://graph.facebook.com/v18.0/{$numberId}/messages" => Http::response(['messages' => [['id' => 'message_id_123']]], 200),
    ]);

    // Mock the AccountResolver to return a test account
    $mockResolver = Mockery::mock(AccountResolver::class);
    $mockResolver->shouldReceive('resolve')->andReturn([
        'token' => 'test_token',
        'number_id' => $numberId,
        'catalog_id' => 'test_catalog_id',
    ]);

    // Ensure that the static instance uses the mocked resolver
    $whatsapp = Whatsapp::getInstance($mockResolver);

    $msg = (object) [
        'text' => ['body' => $message],
    ];

    // Send the message using the WhatsApp facade
    $response = $whatsapp->setNumberId($numberId)->sendMessage($recipient, $msg);

    // Assert the request was made to the correct endpoint
    Http::assertSent(function ($request) use ($numberId, $recipient, $message) {
        return $request->url() === "https://graph.facebook.com/v18.0/{$numberId}/messages" &&
            $request['recipient_type'] === 'individual' &&
            $request['to'] === $recipient &&
            $request['type'] === 'text' &&
            $request['text']['body'] === $message;
    });

    // Check that response is the expected array structure
    expect($response)->toBeString();
    expect($response)->toBe('message_id_123');
});

it('can send a media message', function () {
    $numberId = '1234567890';
    $recipient = '0987654321';
    $mediaType = 'image';
    $mediaUrl = 'https://example.com/image.jpg';
    $caption = 'This is a caption';

    // Mock the HTTP request to the WhatsApp API
    Http::fake([
        "https://graph.facebook.com/v18.0/{$numberId}/messages" => Http::response(['messages' => [['id' => 'message_id_456']]], 200),
    ]);

    // Mock the AccountResolver to return a test account
    $mockResolver = Mockery::mock(AccountResolver::class);
    $mockResolver->shouldReceive('resolve')->andReturn([
        'token' => 'test_token',
        'number_id' => $numberId,
        'catalog_id' => 'test_catalog_id',
    ]);

    // Ensure that the static instance uses the mocked resolver
    $whatsapp = Whatsapp::getInstance($mockResolver);

    $response = $whatsapp->setNumberId($numberId)->sendMedia($recipient, $mediaType, $mediaUrl, $caption);

    // Assert the request was made to the correct endpoint
    Http::assertSent(function ($request) use ($numberId, $recipient, $mediaType, $mediaUrl, $caption) {
        return $request->url() === "https://graph.facebook.com/v18.0/{$numberId}/messages" &&
            $request['recipient_type'] === 'individual' &&
            $request['to'] === $recipient &&
            $request['type'] === $mediaType &&
            $request[$mediaType]['link'] === $mediaUrl &&
            $request[$mediaType]['caption'] === $caption;
    });

    // Check that response is the expected array structure
    expect($response)->toBeString();
    expect($response)->toBe('message_id_456');
});

it('throws an exception when account is not found', function () {
    $numberId = '9999999999'; // Using a number ID that will not be resolved
    $recipient = '1234567890';
    $content = (object) [
        'text' => 'Hello, world!',
    ];

    // Mock the AccountResolver to return null (account not found)
    $mockResolver = Mockery::mock(AccountResolver::class);
    $mockResolver->shouldReceive('resolve')->andReturnNull();

    // Ensure that the static instance uses the mocked resolver
    $whatsapp = Whatsapp::getInstance($mockResolver);

    // Assert that an exception is thrown
    $this->expectException(Exception::class);
    $this->expectExceptionMessage("No WhatsApp account found for number ID: $numberId");

    $whatsapp->setNumberId($numberId)->sendMessage($recipient, $content);
});
