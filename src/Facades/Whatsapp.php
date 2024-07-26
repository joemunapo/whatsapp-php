<?php

namespace Joemunapo\Whatsapp\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \Joemunapo\Whatsapp\Whatsapp useNumberId(string $numberId)
 * @method static \Joemunapo\Whatsapp\Message|null handleWebhook(array $payload, ?\Joemunapo\Whatsapp\Whatsapp $instance = null)
 * @method static string|null sendMessage(string $to, object $content)
 * @method static string|null sendMedia(string $to, string $mediaType, string $mediaUrl, ?string $caption = null)
 * @method static string|null sendTemplate(string $to, string $templateName, string $languageCode, array $components = [])
 * @method static string|null markMessageAsRead(string $phoneNumber, string $messageId)
 * @method static array getMedia(string $mediaId)
 * 
 * @mixins \Joemunapo\Whatsapp\Whatsapp
 * 
 * @see \Joemunapo\Whatsapp\Whatsapp
 */
class Whatsapp extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Joemunapo\Whatsapp\Whatsapp::class;
    }
}