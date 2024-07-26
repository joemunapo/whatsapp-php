<?php

use Joemunapo\Whatsapp\Message;
use Joemunapo\Whatsapp\Whatsapp;

beforeEach(function () {
    $this->mockWhatsapp = Mockery::mock(Whatsapp::class);
});

afterEach(function () {
    Mockery::close();
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

it('can reply to a message and mark as read', function () {
    $msg_id = 'message_id_678';
    $messageData = (object) [
        'from' => '1234567890',
        'id' => 'message_id_123',
        'type' => 'text',
        'text' => ['body' => 'Hello'],
    ];

    $this->mockWhatsapp->shouldReceive('sendMessage')
        ->with('1234567890', Mockery::on(function ($content) {
            return is_object($content) &&
                isset($content->text) &&
                is_array($content->text) &&
                $content->text['body'] === 'Reply message';
        }))
        ->once()
        ->andReturn($msg_id);

    $this->mockWhatsapp->shouldReceive('markMessageAsRead')
        ->once()
        ->andReturn(true);

    $message = new Message($messageData, $this->mockWhatsapp);
    $response = $message->reply('Reply message');

    expect($response)->toBeString();
    expect($response)->toBe($msg_id);
});

it('can reply to a message with media', function () {
    $msg_id = 'message_id_678';
    $messageData = (object) [
        'from' => '1234567890',
        'id' => 'message_id_123',
        'type' => 'text',
        'text' => ['body' => 'Hello'],
    ];

    $this->mockWhatsapp->shouldReceive('sendMedia')
        ->with('1234567890', 'image', 'https://example.com/image.jpg', 'This is a caption')
        ->once()
        ->andReturn($msg_id);

    $message = new Message($messageData, $this->mockWhatsapp);
    $response = $message->replyWithMedia('image', 'https://example.com/image.jpg', 'This is a caption');

    expect($response)->toBeString();
    expect($response)->toBe($msg_id);
});

it('can reply to a message with a template', function () {
    $msg_id = 'message_id_678';
    $messageData = (object) [
        'from' => '1234567890',
        'id' => 'message_id_123',
        'type' => 'text',
        'text' => ['body' => 'Hello'],
    ];

    $this->mockWhatsapp->shouldReceive('sendTemplate')
        ->with('1234567890', 'template_name', 'en', ['component' => 'value'])
        ->once()
        ->andReturn($msg_id);

    $message = new Message($messageData, $this->mockWhatsapp);
    $response = $message->replyWithTemplate('template_name', 'en', ['component' => 'value']);

    expect($response)->toBeString();
    expect($response)->toBe($msg_id);
});

it('can get media content', function () {
    $messageData = (object) [
        'from' => '1234567890',
        'id' => 'message_id_123',
        'type' => 'image',
        'image' => [
            'id' => 'media_id',
            'caption' => 'An image caption',
        ],
    ];

    $this->mockWhatsapp->shouldReceive('getMedia')
        ->with('media_id')
        ->once()
        ->andReturn('media_content');

    $message = new Message($messageData, $this->mockWhatsapp);
    $response = $message->getMediaContent();

    expect($response)->toBeString();
    expect($response)->toBe('media_content');
});
