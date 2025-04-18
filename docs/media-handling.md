# Media Handling

WhatsApp allows sending and receiving various media types including images, videos, documents, and audio files.

## Sending Media

You can send different types of media using the `sendMedia` method:

```php
use Joemunapo\Whatsapp\Facades\Whatsapp;

$whatsapp = Whatsapp::useNumberId('your_whatsapp_number_id');

// Required parameters
$phoneNumber = '1234567890';
$mediaType = 'image'; // Options: image, video, document, audio
$mediaUrl = 'https://example.com/image.jpg'; // Public URL to the media file

// Optional
$caption = 'Check out this great image!';

// Send the media
$messageId = $whatsapp->sendMedia($phoneNumber, $mediaType, $mediaUrl, $caption);
```

### Media Types

- `image`: JPG, PNG, or WebP format (max 5MB)
- `video`: MP4 or 3GPP format (max 16MB)
- `document`: PDF, DOC, DOCX, etc. (max 100MB)
- `audio`: MP3, OGG, or AAC format (max 16MB)

### When Replying to a Message

When handling a webhook, you can use the `replyWithMedia` method:

```php
$message = Whatsapp::handleWebhook($payload);

if ($message) {
    $message->replyWithMedia('image', 'https://example.com/response.jpg', 'Here\'s your requested information');
}
```

## Receiving Media

When a user sends media through WhatsApp, you'll receive it through the webhook. The `handleWebhook` method will parse the media information:

```php
$message = Whatsapp::handleWebhook($payload);

if ($message && $message->isMedia()) {
    // Get media type
    $mediaType = $message->type; // 'image', 'video', 'document', etc.
    
    // Get media ID
    $mediaId = $message->mediaId;
    
    // Get media caption (if any)
    $caption = $message->text;
    
    // Process the media based on type
    processMedia($mediaType, $mediaId, $caption);
}
```

### Retrieving Media Content

Media received through the webhook only includes a media ID. To get the actual media file:

1. First, get the media URL:

```php
// Get media information including URL
$mediaInfo = $message->getMediaContent();

// Or using the WhatsApp instance directly
$mediaInfo = $whatsapp->getMedia($mediaId);
```

2. Then download the media:

```php
// Using the message instance
$response = $message->downloadMedia();

// Or manually
$response = $whatsapp->downLoadMedia($mediaInfo->url);

// Save the file
$filename = 'received_media.' . getExtensionFromType($message->type);
Storage::put('media/' . $filename, $response->body());
```

## Checking Message Types

The Message class provides helper methods to check the message type:

```php
$message = Whatsapp::handleWebhook($payload);

if ($message) {
    if ($message->isText()) {
        // Handle text message
    } elseif ($message->isMedia()) {
        // Handle media message
    } elseif ($message->isLocation()) {
        // Handle location message
    } elseif ($message->isContact()) {
        // Handle contact message
    } elseif ($message->isButton) {
        // Handle button response
    } elseif ($message->isOrder) {
        // Handle order
    }
}
```