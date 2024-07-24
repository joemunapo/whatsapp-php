<?php

use Joemunapo\Whatsapp\Message;
use Joemunapo\Whatsapp\Whatsapp;

beforeEach(function () {
    $this->mockWhatsapp = Mockery::mock(Whatsapp::class);
});

it('initializes a text message correctly', function () {
    $messageData = (object) [
        'from' => '1234567890',
        'id' => 'msg_id',
        'type' => 'text',
        'text' => ['body' => 'Hello, World!'],
    ];

    $message = new Message($messageData, $this->mockWhatsapp);

    expect($message->from)->toBe('1234567890');
    expect($message->id)->toBe('msg_id');
    expect($message->type)->toBe('text');
    expect($message->text)->toBe('Hello, World!');
    expect($message->isText())->toBeTrue();
    expect($message->isMedia())->toBeFalse();
});

it('initializes a media message correctly', function () {
    $messageData = (object) [
        'from' => '1234567890',
        'id' => 'msg_id',
        'type' => 'image',
        'image' => [
            'id' => 'media_id',
            'caption' => 'An image caption',
        ],
    ];

    $message = new Message($messageData, $this->mockWhatsapp);

    expect($message->from)->toBe('1234567890');
    expect($message->id)->toBe('msg_id');
    expect($message->type)->toBe('image');
    expect($message->text)->toBe('An image caption');
    expect($message->mediaId)->toBe('media_id');
    expect($message->isText())->toBeFalse();
    expect($message->isMedia())->toBeTrue();
});

it('can reply to a message', function () {
    $messageData = (object) [
        'from' => '1234567890',
        'id' => 'msg_id',
        'type' => 'text',
        'text' => ['body' => 'Hello'],
    ];

    $this->mockWhatsapp->shouldReceive('sendMessage')
        ->with('1234567890', 'Reply message')
        ->once()
        ->andReturn(['message_id' => 'reply_id']);

    $message = new Message($messageData, $this->mockWhatsapp);
    $result = $message->reply('Reply message');

    expect($result)->toBe(['message_id' => 'reply_id']);
});

it('can mark a message as read', function () {
    $messageData = (object) [
        'from' => '1234567890',
        'id' => 'msg_id',
        'type' => 'text',
        'text' => ['body' => 'Hello'],
    ];

    $this->mockWhatsapp->shouldReceive('markMessageAsRead')
        ->with('1234567890', 'msg_id')
        ->once()
        ->andReturn(['success' => true]);

    $message = new Message($messageData, $this->mockWhatsapp);
    $result = $message->markAsRead();

    expect($result)->toBe(['success' => true]);
});
