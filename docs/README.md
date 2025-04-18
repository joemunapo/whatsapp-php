# WhatsApp PHP for Laravel

> A comprehensive Laravel package for integrating with the WhatsApp Cloud API.

This package provides a simple and efficient way to integrate WhatsApp Cloud API functionality into your Laravel application. It's designed to be flexible and support multiple WhatsApp accounts, making it ideal for multi-tenant applications.

## Features

- Send text messages
- Send interactive messages (buttons, lists, product lists)
- Send rich media (images, videos, documents)
- Send template messages
- Handle incoming messages via webhooks
- Mark messages as read
- Retrieve media content
- User session management
- Events for sent/received messages
- Support for multiple WhatsApp accounts

## What's in the documentation

- [Installation Guide](installation.md) - How to install and configure the package
- [Basic Usage](basic-usage.md) - How to start sending messages
- [Interactive Messages](interactive-messages.md) - How to send buttons, lists, and more
- [Media Handling](media-handling.md) - How to send and receive media
- [Templates](templates.md) - How to use message templates
- [Webhook Handling](webhook-handling.md) - How to process incoming messages
- [Session Management](session-management.md) - How to maintain conversation state
- [Account Management](account-management.md) - How to manage multiple WhatsApp accounts
- [Events System](events-system.md) - How to use the package's event system
- [Improvement Suggestions](improvements.md) - Ideas for future enhancements

## Quick Start

```php
use Joemunapo\Whatsapp\Facades\Whatsapp;

// Initialize with a WhatsApp number ID
$whatsapp = Whatsapp::useNumberId('your_whatsapp_number_id');

// Send a simple text message
$whatsapp->sendMessage('1234567890', (object) [
    'type' => 'text',
    'text' => [
        'body' => 'Hello from WhatsApp PHP!'
    ]
]);
```